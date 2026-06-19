<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ServiceCategoryController extends Controller
{
    /**
     * Get list of service categories.
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        $isAdmin = $user && ($user->hasRole('super_admin') || $user->hasRole('admin_marketplace'));

        // Admin can request all categories (active/inactive), regular users only see active ones
        if ($request->has('all') && $isAdmin) {
            $categories = ServiceCategory::orderBy('name')->get();
        } else {
            $categories = ServiceCategory::where('is_active', true)->orderBy('name')->get();
        }

        return response()->json($categories);
    }

    /**
     * Create a new category (Admin only).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:service_categories,name'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = ServiceCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => $request->get('is_active', true),
        ]);

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($category)
            ->log("Membuat kategori jasa baru: {$category->name}");

        return response()->json([
            'message' => 'Kategori jasa berhasil dibuat.',
            'category' => $category,
        ], 201);
    }

    /**
     * Update a category (Admin only).
     */
    public function update(Request $request, $id)
    {
        $category = ServiceCategory::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:service_categories,name,'.$category->id],
            'is_active' => ['required', 'boolean'],
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => $request->is_active,
        ]);

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($category)
            ->log("Memperbarui kategori jasa: {$category->name}");

        return response()->json([
            'message' => 'Kategori jasa berhasil diperbarui.',
            'category' => $category,
        ]);
    }

    /**
     * Delete a category (Admin only).
     */
    public function destroy($id)
    {
        $category = ServiceCategory::findOrFail($id);

        // Validation check: cannot delete if category is used by active services
        if (Schema::hasTable('services')) {
            $isUsed = DB::table('services')->where('service_category_id', $category->id)->exists();
            if ($isUsed) {
                return response()->json([
                    'message' => 'Gagal menghapus. Kategori sedang digunakan oleh data jasa aktif.',
                ], 422);
            }
        }

        $categoryName = $category->name;
        $category->delete();

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->log("Menghapus kategori jasa: {$categoryName}");

        return response()->json([
            'message' => "Kategori jasa {$categoryName} berhasil dihapus.",
        ]);
    }
}
