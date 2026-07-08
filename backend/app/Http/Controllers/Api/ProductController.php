<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantImage;
use App\Models\Store;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\WebPushService;
use App\Models\Favorite;

class ProductController extends Controller
{
    /**
     * Display a listing of active products (Public catalog).
     */
    public function index(Request $request)
    {
        $query = Product::with(['store', 'category', 'primaryImage', 'images', 'variants', 'variants.images'])
            ->whereHas('store', function ($q) {
                $q->where('status', 'active');
            })
            ->whereIn('status', ['active', 'out_of_stock']);

        // Filter by category
        if ($request->has('product_category_id') && $request->product_category_id) {
            $query->where('product_category_id', $request->product_category_id);
        }

        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        // Exclude specific product
        if ($request->has('exclude') && $request->exclude) {
            $query->where('id', '!=', $request->exclude);
        }

        // Exclude products from specific store
        if ($request->has('exclude_store') && $request->exclude_store) {
            $query->where('store_id', '!=', $request->exclude_store);
        }

        // Per page
        $perPage = min((int) $request->get('per_page', 12), 50);

        // Search by keyword
        if ($request->has('search') && $request->search) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Filter by featured
        if ($request->has('is_featured') && $request->is_featured !== null) {
            $query->where('is_featured', filter_var($request->is_featured, FILTER_VALIDATE_BOOLEAN));
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Display a specific product by slug (Public).
     */
    public function show($slug)
    {
        $product = Product::with(['store.alumniProfile.user', 'store.deliveryFees', 'category', 'images', 'variants', 'variants.images'])
            ->where('slug', $slug)
            ->firstOrFail();

        // If the store is not active or the product is inactive, restrict view to owner or admin
        if ($product->store->status !== 'active' || $product->status === 'inactive') {
            $user = auth('sanctum')->user();
            if (! $user) {
                return response()->json(['message' => 'Akses ditolak. Produk tidak tersedia.'], 403);
            }

            $isOwner = $product->store->alumniProfile->user_id === $user->id;
            $isAdmin = $user->hasRole('super_admin') || $user->hasRole('admin_marketplace');

            if (! $isOwner && ! $isAdmin) {
                return response()->json(['message' => 'Akses ditolak. Produk tidak tersedia.'], 403);
            }
        }

        return response()->json([
            'product' => $product,
        ]);
    }

    /**
     * Display a specific product by ID (Public).
     */
    public function showById($id)
    {
        $product = Product::with(['store.alumniProfile.user', 'store.deliveryFees', 'category', 'images', 'variants'])
            ->findOrFail($id);

        // If the store is not active or the product is inactive, restrict view to owner or admin
        if ($product->store->status !== 'active' || $product->status === 'inactive') {
            $user = auth('sanctum')->user();
            if (! $user) {
                return response()->json(['message' => 'Akses ditolak. Produk tidak tersedia.'], 403);
            }

            $isOwner = $product->store->alumniProfile->user_id === $user->id;
            $isAdmin = $user->hasRole('super_admin') || $user->hasRole('admin_marketplace');

            if (! $isOwner && ! $isAdmin) {
                return response()->json(['message' => 'Akses ditolak. Produk tidak tersedia.'], 403);
            }
        }

        return response()->json([
            'product' => $product,
        ]);
    }

    /**
     * List all products for the authenticated seller.
     */
    public function sellerProducts(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json(['message' => 'Toko tidak ditemukan.'], 404);
        }

        $products = Product::with(['category', 'primaryImage', 'variants', 'variants.images'])
            ->where('store_id', $profile->store->id)
            ->latest()
            ->get();

        return response()->json($products);
    }

    /**
     * Create a new product (Seller).
     */
    public function store(CreateProductRequest $request)
    {
        Gate::authorize('create', Product::class);

        $profile = $request->user()->profile;
        $store = $profile->store;

        $data = $request->validated();
        unset($data['variants']);
        $data['store_id'] = $store->id;

        // Auto force status out_of_stock if stock is 0 (regular only), unless variants have stock
        $isPreOrder = ($data['product_type'] ?? 'regular') === 'pre_order';
        $hasVariantStock = $request->has('variants') && collect($request->variants)->sum('stock') > 0;
        if ($data['stock'] == 0 && ! $isPreOrder && ! $hasVariantStock) {
            $data['status'] = 'out_of_stock';
        }

        // Auto-generate unique slug
        $slug = Str::slug($data['name']);
        $originalSlug = $slug;
        $count = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count;
            $count++;
        }
        $data['slug'] = $slug;

        $product = Product::create($data);

        if ($request->has('variants') && is_array($request->variants)) {
            foreach ($request->variants as $i => $v) {
                $product->variants()->create([
                    'name' => $v['name'],
                    'price' => $v['price'] ?? $product->price,
                    'stock' => $v['stock'] ?? 0,
                    'sort_order' => $i,
                ]);
            }
        }

        activity()
            ->performedOn($product)
            ->log("Membuat produk baru bernama: {$product->name}");

        // Notify store followers
        $followerIds = Favorite::where('favoritable_type', 'App\Models\Store')
            ->where('favoritable_id', $store->id)
            ->pluck('user_id');
        if ($followerIds->isNotEmpty()) {
            $push = app(WebPushService::class);
            foreach ($followerIds as $uid) {
                $push->sendToUser($uid,
                    'Produk Baru: ' . $store->name,
                    $product->name . ' · Rp' . number_format($product->price, 0, ',', '.'),
                    '/logo_unmul.png',
                    '/buyer/products/' . $product->slug
                );
            }
        }

        return response()->json([
            'message' => 'Produk berhasil dibuat.',
            'product' => $product->load(['category', 'variants']),
        ], 201);
    }

    /**
     * Show a seller's product details (Seller).
     */
    public function sellerShow($id)
    {
        $product = Product::with(['category', 'images', 'variants', 'variants.images'])->findOrFail($id);
        Gate::authorize('update', $product);

        return response()->json([
            'product' => $product,
        ]);
    }

    /**
     * Update an existing product (Seller).
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        Gate::authorize('update', $product);

        $data = $request->validated();
        unset($data['variants']);

        // Auto force status out_of_stock if stock is 0 (regular only), unless variants have stock
        $isPreOrder = ($data['product_type'] ?? $product->product_type) === 'pre_order';
        $hasVariantStock = $request->has('variants') && collect($request->variants)->sum('stock') > 0;
        if ($data['stock'] == 0 && ! $isPreOrder && ! $hasVariantStock) {
            $data['status'] = 'out_of_stock';
        }

        // Regenerate slug if name changed
        if ($product->name !== $data['name']) {
            $slug = Str::slug($data['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug.'-'.$count;
                $count++;
            }
            $data['slug'] = $slug;
        }

        $product->update($data);

        if ($request->has('variants')) {
            $existingVariants = $product->variants()->with('images')->get()->keyBy('name');
            $product->variants()->whereNotIn('name', collect($request->variants)->pluck('name'))->delete();
            foreach ($request->variants as $i => $v) {
                $variant = $existingVariants->get($v['name']);
                if ($variant) {
                    $variant->update([
                        'price' => $v['price'] ?? $product->price,
                        'stock' => $v['stock'] ?? 0,
                        'sort_order' => $i,
                    ]);
                } else {
                    $variant = $product->variants()->create([
                        'name' => $v['name'],
                        'price' => $v['price'] ?? $product->price,
                        'stock' => $v['stock'] ?? 0,
                        'sort_order' => $i,
                    ]);
                }
            }
        }

        activity()
            ->performedOn($product)
            ->log("Memperbarui detail produk: {$product->name}");

        return response()->json([
            'message' => 'Produk berhasil diperbarui.',
            'product' => $product->load(['category', 'images', 'variants', 'variants.images']),
        ]);
    }

    /**
     * Delete a product (Seller).
     */
    public function destroy($id)
    {
        $product = Product::with('images')->findOrFail($id);
        Gate::authorize('delete', $product);

        DB::transaction(function () use ($product) {
            // Delete image files from storage
            foreach ($product->images as $image) {
                $oldPath = str_replace(asset('storage/'), '', $image->image_path);
                Storage::disk('public')->delete($oldPath);
            }
            // Cascades deletion of image records in database due to foreignUuid cascadeOnDelete
            $product->delete();
        });

        activity()
            ->performedOn($product)
            ->log("Menghapus produk: {$product->name} beserta seluruh fotonya.");

        return response()->json([
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    /**
     * Upload / Update primary image (Seller).
     */
    public function uploadImage(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        Gate::authorize('update', $product);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $product) {
            // Check if there is an existing primary image
            $oldPrimary = $product->images()->where('is_primary', true)->first();
            if ($oldPrimary) {
                // Delete old file
                $oldPath = str_replace(asset('storage/'), '', $oldPrimary->image_path);
                Storage::disk('public')->delete($oldPath);
                $oldPrimary->delete();
            }

            // Store new file
            $path = (new ImageService)->storeAsWebP($request->file('image'), 'products/images');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => asset('storage/'.$path),
                'is_primary' => true,
            ]);
        });

        return response()->json([
            'message' => 'Foto utama produk berhasil diperbarui.',
            'product' => $product->load('images'),
        ]);
    }

    /**
     * Upload additional gallery images (Seller).
     */
    public function uploadGallery(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        Gate::authorize('update', $product);

        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        // Max 5 gallery photos limit
        $currentGalleryCount = $product->images()->where('is_primary', false)->count();
        $newCount = count($request->file('images'));

        if (($currentGalleryCount + $newCount) > 5) {
            return response()->json([
                'message' => 'Maksimal 5 foto galeri tambahan diperbolehkan.',
            ], 422);
        }

        $uploadedImages = [];
        DB::transaction(function () use ($request, $product, &$uploadedImages) {
            foreach ($request->file('images') as $file) {
                $path = (new ImageService)->storeAsWebP($file, 'products/gallery');
                $img = ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => asset('storage/'.$path),
                    'is_primary' => false,
                ]);
                $uploadedImages[] = $img;
            }
        });

        return response()->json([
            'message' => 'Foto galeri berhasil ditambahkan.',
            'images' => $uploadedImages,
            'product' => $product->load('images'),
        ]);
    }

    public function uploadVariantImage(Request $request, $productId, $variantId)
    {
        $product = Product::with('variants')->findOrFail($productId);
        Gate::authorize('update', $product);

        $variant = $product->variants()->findOrFail($variantId);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($variant->images()->count() >= 5) {
            return response()->json(['message' => 'Maksimal 5 foto per varian.'], 422);
        }

        $path = (new ImageService)->storeAsWebP($request->file('image'), 'products/variants');

        $isPrimary = $variant->images()->count() === 0;
        $img = $variant->images()->create([
            'image_path' => $path,
            'is_primary' => $isPrimary,
        ]);

        return response()->json([
            'message' => 'Foto varian berhasil diunggah.',
            'image' => $img,
        ], 201);
    }

    public function deleteVariantImage(Request $request, $productId, $variantId, $imageId)
    {
        $product = Product::findOrFail($productId);
        Gate::authorize('update', $product);

        $variant = $product->variants()->findOrFail($variantId);
        $image = $variant->images()->findOrFail($imageId);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json(['message' => 'Foto varian berhasil dihapus.']);
    }

    /**
     * Delete a specific gallery image (Seller).
     */
    public function deleteImage(Request $request, $productId, $imageId)
    {
        $product = Product::findOrFail($productId);
        Gate::authorize('update', $product);

        $image = ProductImage::where('product_id', $product->id)->findOrFail($imageId);

        // Delete file from storage
        $oldPath = str_replace(asset('storage/'), '', $image->image_path);
        Storage::disk('public')->delete($oldPath);

        $image->delete();

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
            'product' => $product->load('images'),
        ]);
    }

    /**
     * List all products of a store, any status (Admin moderation).
     */
    public function adminStoreProducts($storeId)
    {
        $products = Product::with(['category', 'primaryImage'])
            ->where('store_id', $storeId)
            ->latest()
            ->get();

        return response()->json($products);
    }

    /**
     * Delete a product (Admin moderation, bypasses ownership).
     */
    public function adminDestroy($id)
    {
        $product = Product::with('images')->findOrFail($id);

        DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                $oldPath = str_replace(asset('storage/'), '', $image->image_path);
                Storage::disk('public')->delete($oldPath);
            }
            $product->delete();
        });

        activity()
            ->causedBy(auth()->user())
            ->performedOn($product)
            ->log("Admin menghapus produk: {$product->name}.");

        return response()->json([
            'message' => 'Produk berhasil dihapus oleh admin.',
        ]);
    }

    /**
     * Toggle product active/inactive (Admin moderation / sembunyikan).
     */
    public function adminToggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';
        $product->update(['status' => $newStatus]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($product)
            ->log("Admin mengubah status produk {$product->name} menjadi: {$newStatus}.");

        return response()->json([
            'message' => "Status produk diubah menjadi {$newStatus}.",
            'product' => $product->load(['category', 'primaryImage']),
        ]);
    }
}
