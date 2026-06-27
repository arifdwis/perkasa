<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\NewOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Process checkout from the cart.
     */
    public function checkout(Request $request)
    {
        $directProductId = $request->input('product_id');
        $directQuantity = intval($request->input('quantity', 1));

        $grouped = [];
        $isDirect = false;
        $cart = null;

        if ($directProductId) {
            $product = \App\Models\Product::with(['store.deliveryFees', 'store.alumniProfile'])->find($directProductId);
            if (! $product || $product->status === 'inactive') {
                return response()->json(['message' => 'Produk tidak aktif atau tidak tersedia.'], 400);
            }
            if ($product->stock < $directQuantity || $product->status === 'out_of_stock') {
                return response()->json(['message' => "Stok produk {$product->name} tidak mencukupi. Hanya tersedia {$product->stock} unit."], 400);
            }
            $store = $product->store;
            if (! $store || $store->status !== 'active') {
                return response()->json(['message' => "Toko untuk produk {$product->name} tidak aktif."], 400);
            }
            if ($store->alumniProfile && $store->alumniProfile->user_id === $request->user()->id) {
                return response()->json(['message' => 'Anda tidak dapat membeli produk dari toko Anda sendiri.'], 400);
            }

            $mockItem = new \stdClass();
            $mockItem->product = $product;
            $mockItem->quantity = $directQuantity;

            $grouped[$store->id][] = $mockItem;
            $isDirect = true;
        } else {
            $cart = $request->user()->cart()->firstOrCreate();
            $cart->load(['items.product.store.deliveryFees', 'items.product.store.alumniProfile']);

            if ($cart->items->isEmpty()) {
                return response()->json(['message' => 'Keranjang belanja kosong.'], 400);
            }

            // Group cart items by store
            foreach ($cart->items as $item) {
                $product = $item->product;
                if (! $product || $product->status === 'inactive') {
                    return response()->json(['message' => "Produk {$product?->name} tidak aktif atau tidak tersedia."], 400);
                }
                if ($product->stock < $item->quantity || $product->status === 'out_of_stock') {
                    return response()->json(['message' => "Stok produk {$product->name} tidak mencukupi. Hanya tersedia {$product->stock} unit."], 400);
                }
                $store = $product->store;
                if (! $store || $store->status !== 'active') {
                    return response()->json(['message' => "Toko untuk produk {$product->name} tidak aktif."], 400);
                }
                if ($store->alumniProfile && $store->alumniProfile->user_id === $request->user()->id) {
                    return response()->json(['message' => "Anda tidak dapat membeli produk {$product->name} dari toko Anda sendiri."], 400);
                }

                $grouped[$store->id][] = $item;
            }
        }

        // Check if any store is per_wilayah, in which case wilayah_antar is required
        $requiresWilayah = false;
        foreach ($grouped as $storeId => $items) {
            $store = $items[0]->product->store;
            if ($store->delivery_type === 'per_wilayah') {
                $requiresWilayah = true;
            }
        }

        $request->validate([
            'nama_penerima' => ['required', 'string', 'max:255'],
            'telepon_penerima' => ['required', 'string', 'max:50'],
            'alamat_penerima' => ['required', 'string'],
            'wilayah_antar' => [$requiresWilayah ? 'required' : 'nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $ordersCreated = [];

        try {
            DB::transaction(function () use ($request, $grouped, &$ordersCreated, $cart, $isDirect) {
                $user = $request->user();

                foreach ($grouped as $storeId => $items) {
                    $store = $items[0]->product->store;

                    // Calculate subtotal
                    $subtotal = 0;
                    foreach ($items as $item) {
                        $subtotal += floatval($item->product->price) * $item->quantity;
                    }

                    // Calculate delivery fee
                    $biayaAntar = 0;
                    if ($store->delivery_type === 'fixed') {
                        $biayaAntar = floatval($store->fixed_delivery_fee);
                    } elseif ($store->delivery_type === 'per_wilayah') {
                        $wilayah = $request->wilayah_antar;
                        $deliveryFeeOption = $store->deliveryFees()
                            ->where('wilayah', $wilayah)
                            ->first();

                        if (! $deliveryFeeOption) {
                            throw new \Exception("Toko {$store->name} tidak melayani pengantaran ke wilayah {$wilayah}.");
                        }
                        $biayaAntar = floatval($deliveryFeeOption->fee);
                    }

                    $total = $subtotal + $biayaAntar;

                    // Generate unique order number: ORD-YYYYMMDD-RAND8
                    $orderNumber = 'ORD-'.date('Ymd').'-'.strtoupper(Str::random(8));

                    // Create Order
                    $order = Order::create([
                        'order_number' => $orderNumber,
                        'user_id' => $user->id,
                        'store_id' => $store->id,
                        'nama_penerima' => $request->nama_penerima,
                        'telepon_penerima' => $request->telepon_penerima,
                        'alamat_penerima' => $request->alamat_penerima,
                        'wilayah_antar' => $request->wilayah_antar,
                        'subtotal' => $subtotal,
                        'biaya_antar' => $biayaAntar,
                        'total' => $total,
                        'payment_method' => 'COD',
                        'status' => 'menunggu_konfirmasi',
                        'catatan' => $request->catatan,
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                    ]);

                    // Log initial status
                    $order->statusLogs()->create([
                        'status' => 'menunggu_konfirmasi',
                        'description' => 'Pesanan berhasil dibuat, menunggu konfirmasi penjual.',
                        'changed_by' => $user->id,
                    ]);

                    // Record to Spatie Activity Log
                    activity()
                        ->performedOn($order)
                        ->log("Pesanan baru dengan nomor {$order->order_number} berhasil dibuat.");

                    // Create Order Items and reduce stock
                    foreach ($items as $item) {
                        $product = $item->product;

                        // Double check stock inside transaction
                        $productFresh = $product->fresh();
                        if ($productFresh->stock < $item->quantity || $productFresh->status === 'inactive') {
                            throw new \Exception("Stok produk {$product->name} tidak mencukupi atau produk telah dinonaktifkan. Checkout dibatalkan.");
                        }

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'price' => floatval($product->price),
                            'quantity' => $item->quantity,
                        ]);

                        // Reduce stock
                        $newStock = $productFresh->stock - $item->quantity;
                        $productFresh->update([
                            'stock' => $newStock,
                            'status' => $newStock === 0 ? 'out_of_stock' : $productFresh->status,
                        ]);
                    }

                    $ordersCreated[] = $order;
                }

                // Empty the cart only if not direct checkout
                if (! $isDirect && $cart) {
                    $cart->items()->delete();
                }
            });

            // Trigger notifications to sellers
            foreach ($ordersCreated as $order) {
                $seller = $order->store->alumniProfile?->user;
                if ($seller) {
                    $seller->notify(new NewOrderNotification($order));
                }
            }

            return response()->json([
                'message' => 'Checkout berhasil. Pesanan Anda telah dibuat.',
                'orders' => array_map(fn ($o) => [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'total' => floatval($o->total),
                ], $ordersCreated),
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
