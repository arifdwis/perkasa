<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Get the authenticated user's cart.
     */
    public function index(Request $request)
    {
        $cart = $request->user()->cart()->firstOrCreate();

        $cart->load([
            'items.product.store.deliveryFees',
            'items.product.category',
            'items.product.primaryImage',
        ]);

        $groupedItems = [];
        $subtotal = 0;

        foreach ($cart->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            // Skip inactive products from the listing (or store is inactive)
            $store = $product->store;
            if (! $store || $store->status !== 'active' || $product->status === 'inactive') {
                continue;
            }

            if (! isset($groupedItems[$store->id])) {
                $groupedItems[$store->id] = [
                    'store_id' => $store->id,
                    'store_name' => $store->name,
                    'store_kota' => $store->kota,
                    'delivery_type' => $store->delivery_type,
                    'fixed_delivery_fee' => floatval($store->fixed_delivery_fee),
                    'delivery_fees' => $store->deliveryFees->map(fn ($df) => [
                        'id' => $df->id,
                        'wilayah' => $df->wilayah,
                        'fee' => floatval($df->fee),
                    ]),
                    'items' => [],
                ];
            }

            $itemPrice = floatval($product->price);
            $itemSubtotal = $itemPrice * $item->quantity;
            $subtotal += $itemSubtotal;

            $groupedItems[$store->id]['items'][] = [
                'id' => $item->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $itemPrice,
                'stock' => $product->stock,
                'status' => $product->status,
                'primary_image' => $product->primaryImage,
                'category_name' => $product->category?->name,
                'quantity' => $item->quantity,
                'subtotal' => $itemSubtotal,
            ];
        }

        return response()->json([
            'cart_id' => $cart->id,
            'grouped_items' => array_values($groupedItems),
            'subtotal' => $subtotal,
        ]);
    }

    /**
     * Add a product to the user's cart.
     */
    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::with('store.alumniProfile')->find($request->product_id);

        // Ensure product is active and its store is active
        if ($product->status === 'inactive' || ! $product->store || $product->store->status !== 'active') {
            return response()->json(['message' => 'Produk tidak aktif atau toko sedang dinonaktifkan.'], 400);
        }

        // Prevent seller from buying own products
        if ($product->store->alumniProfile && $product->store->alumniProfile->user_id === $request->user()->id) {
            return response()->json(['message' => 'Anda tidak dapat membeli produk dari toko Anda sendiri.'], 400);
        }

        // Check stock availability
        if ($product->stock <= 0 || $product->status === 'out_of_stock') {
            return response()->json(['message' => 'Produk out of stock tidak dapat masuk keranjang.'], 400);
        }

        $cart = $request->user()->cart()->firstOrCreate();

        // Check if item already exists in the cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $currentQuantity = $cartItem ? $cartItem->quantity : 0;
        $newQuantity = $currentQuantity + $request->quantity;

        // Ensure total quantity does not exceed available stock
        if ($newQuantity > $product->stock) {
            return response()->json([
                'message' => "Stok tidak mencukupi. Hanya tersedia {$product->stock} unit.",
            ], 400);
        }

        if ($cartItem) {
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $newQuantity,
            ]);
        }

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke keranjang.',
        ], 201);
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $request->user()->cart()->firstOrCreate();
        $cartItem = CartItem::where('cart_id', $cart->id)->find($id);

        if (! $cartItem) {
            return response()->json(['message' => 'Item keranjang tidak ditemukan.'], 404);
        }

        $product = $cartItem->product;
        if (! $product || $product->status === 'inactive') {
            return response()->json(['message' => 'Produk tidak tersedia.'], 400);
        }

        // Validate quantity against stock
        if ($request->quantity > $product->stock) {
            return response()->json([
                'message' => "Stok tidak mencukupi. Hanya tersedia {$product->stock} unit.",
            ], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'message' => 'Kuantitas keranjang berhasil diperbarui.',
        ]);
    }

    /**
     * Delete a cart item.
     */
    public function deleteItem(Request $request, $id)
    {
        $cart = $request->user()->cart()->firstOrCreate();
        $cartItem = CartItem::where('cart_id', $cart->id)->find($id);

        if (! $cartItem) {
            return response()->json(['message' => 'Item keranjang tidak ditemukan.'], 404);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus dari keranjang.',
        ]);
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request)
    {
        $cart = $request->user()->cart()->firstOrCreate();
        $cart->items()->delete();

        return response()->json([
            'message' => 'Keranjang belanja berhasil dikosongkan.',
        ]);
    }
}
