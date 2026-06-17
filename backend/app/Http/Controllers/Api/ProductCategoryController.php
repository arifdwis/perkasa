<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    /**
     * Get list of product categories.
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        $isAdmin = $user && ($user->hasRole('super_admin') || $user->hasRole('admin_marketplace'));

        // Admin can request all categories (active/inactive), regular users only see active ones
        if ($request->has('all') && $isAdmin) {
            $categories = ProductCategory::orderBy('name')->get();
        } else {
            $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        }

        return response()->json($categories);
    }

    /**
     * Create a new category (Admin only).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:product_categories,name'],
            'is_active' => ['nullable', 'boolean']
        ]);

        $category = ProductCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => $request->get('is_active', true)
        ]);

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($category)
            ->log("Membuat kategori produk baru: {$category->name}");

        return response()->json([
            'message' => 'Kategori produk berhasil dibuat.',
            'category' => $category
        ], 201);
    }

    /**
     * Update a category (Admin only).
     */
    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:product_categories,name,' . $category->id],
            'is_active' => ['required', 'boolean']
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => $request->is_active
        ]);

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($category)
            ->log("Memperbarui kategori produk: {$category->name}");

        return response()->json([
            'message' => 'Kategori produk berhasil diperbarui.',
            'category' => $category
        ]);
    }

    /**
     * Delete a category (Admin only).
     */
    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);

        // Validation check: cannot delete if category is used by active products
        if (Schema::hasTable('products')) {
            $isUsed = DB::table('products')->where('product_category_id', $category->id)->exists();
            if ($isUsed) {
                return response()->json([
                    'message' => 'Gagal menghapus. Kategori sedang digunakan oleh data produk aktif.'
                ], 422);
            }
        }

        $categoryName = $category->name;
        $category->delete();

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->log("Menghapus kategori produk: {$categoryName}");

        return response()->json([
            'message' => "Kategori produk {$categoryName} berhasil dihapus."
        ]);
    }
}
