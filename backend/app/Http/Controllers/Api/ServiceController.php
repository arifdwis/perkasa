<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Models\ServiceImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Display a listing of active services (Public catalog).
     */
    public function index(Request $request)
    {
        $query = Service::with(['store', 'category', 'primaryImage'])
            ->whereHas('store', function ($q) {
                $q->where('status', 'active');
            })
            ->where('status', 'active');

        // Filter by category
        if ($request->has('service_category_id') && $request->service_category_id) {
            $query->where('service_category_id', $request->service_category_id);
        }

        // Filter by store
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

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
                $query->orderBy('price_from', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_from', 'desc');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $services = $query->paginate(15);

        return response()->json($services);
    }

    /**
     * Display a specific service by slug (Public).
     */
    public function show($slug)
    {
        $service = Service::with(['store.alumniProfile.user', 'category', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        // If store is not active or service is inactive, restrict view to owner or admin
        if ($service->store->status !== 'active' || $service->status === 'inactive') {
            $user = auth('sanctum')->user();
            if (!$user) {
                return response()->json(['message' => 'Akses ditolak. Layanan tidak tersedia.'], 403);
            }

            $isOwner = $service->store->alumniProfile->user_id === $user->id;
            $isAdmin = $user->hasRole('super_admin') || $user->hasRole('admin_marketplace');

            if (!$isOwner && !$isAdmin) {
                return response()->json(['message' => 'Akses ditolak. Layanan tidak tersedia.'], 403);
            }
        }

        return response()->json([
            'service' => $service
        ]);
    }

    /**
     * List all services for the authenticated seller.
     */
    public function sellerServices(Request $request)
    {
        $profile = $request->user()->profile;
        if (!$profile || !$profile->store) {
            return response()->json(['message' => 'Toko tidak ditemukan.'], 404);
        }

        $services = Service::with(['category', 'primaryImage'])
            ->where('store_id', $profile->store->id)
            ->latest()
            ->get();

        return response()->json($services);
    }

    /**
     * Create a new service (Seller).
     */
    public function store(CreateServiceRequest $request)
    {
        Gate::authorize('create', Service::class);

        $profile = $request->user()->profile;
        $store = $profile->store;

        $data = $request->validated();
        $data['store_id'] = $store->id;

        // Auto-generate unique slug
        $slug = Str::slug($data['name']);
        $originalSlug = $slug;
        $count = 1;
        while (Service::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        $data['slug'] = $slug;

        $service = Service::create($data);

        activity()
            ->performedOn($service)
            ->log("Membuat jasa baru bernama: {$service->name}");

        return response()->json([
            'message' => 'Jasa berhasil dibuat.',
            'service' => $service->load(['category'])
        ], 201);
    }

    /**
     * Show a seller's service details (Seller).
     */
    public function sellerShow($id)
    {
        $service = Service::with(['category', 'images'])->findOrFail($id);
        Gate::authorize('update', $service);

        return response()->json([
            'service' => $service
        ]);
    }

    /**
     * Update an existing service (Seller).
     */
    public function update(UpdateServiceRequest $request, $id)
    {
        $service = Service::findOrFail($id);
        Gate::authorize('update', $service);

        $data = $request->validated();

        // Regenerate slug if name changed
        if ($service->name !== $data['name']) {
            $slug = Str::slug($data['name']);
            $originalSlug = $slug;
            $count = 1;
            while (Service::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            $data['slug'] = $slug;
        }

        $service->update($data);

        activity()
            ->performedOn($service)
            ->log("Memperbarui detail jasa: {$service->name}");

        return response()->json([
            'message' => 'Jasa berhasil diperbarui.',
            'service' => $service->load(['category', 'images'])
        ]);
    }

    /**
     * Delete a service (Seller).
     */
    public function destroy($id)
    {
        $service = Service::with('images')->findOrFail($id);
        Gate::authorize('delete', $service);

        DB::transaction(function () use ($service) {
            // Delete portfolio files
            foreach ($service->images as $image) {
                $oldPath = str_replace(asset('storage/'), '', $image->image_path);
                Storage::disk('public')->delete($oldPath);
            }
            $service->delete();
        });

        activity()
            ->performedOn($service)
            ->log("Menghapus jasa: {$service->name} beserta seluruh portofolionya.");

        return response()->json([
            'message' => 'Jasa berhasil dihapus.'
        ]);
    }

    /**
     * Upload / Update primary image (Seller).
     */
    public function uploadImage(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        Gate::authorize('update', $service);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $service) {
            $oldPrimary = $service->images()->where('is_primary', true)->first();
            if ($oldPrimary) {
                $oldPath = str_replace(asset('storage/'), '', $oldPrimary->image_path);
                Storage::disk('public')->delete($oldPath);
                $oldPrimary->delete();
            }

            $path = $request->file('image')->store('services/images', 'public');

            ServiceImage::create([
                'service_id' => $service->id,
                'image_path' => asset('storage/' . $path),
                'is_primary' => true
            ]);
        });

        return response()->json([
            'message' => 'Foto utama jasa berhasil diperbarui.',
            'service' => $service->load('images')
        ]);
    }

    /**
     * Upload additional portfolio images (Seller).
     */
    public function uploadPortfolio(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        Gate::authorize('update', $service);

        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        // Max 5 portfolio images check
        $currentPortfolioCount = $service->images()->where('is_primary', false)->count();
        $newCount = count($request->file('images'));

        if (($currentPortfolioCount + $newCount) > 5) {
            return response()->json([
                'message' => 'Maksimal 5 foto portofolio tambahan diperbolehkan.'
            ], 422);
        }

        $uploadedImages = [];
        DB::transaction(function () use ($request, $service, &$uploadedImages) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('services/portfolio', 'public');
                $img = ServiceImage::create([
                    'service_id' => $service->id,
                    'image_path' => asset('storage/' . $path),
                    'is_primary' => false
                ]);
                $uploadedImages[] = $img;
            }
        });

        return response()->json([
            'message' => 'Foto portofolio berhasil ditambahkan.',
            'images' => $uploadedImages,
            'service' => $service->load('images')
        ]);
    }

    /**
     * Delete a specific portfolio image (Seller).
     */
    public function deleteImage(Request $request, $serviceId, $imageId)
    {
        $service = Service::findOrFail($serviceId);
        Gate::authorize('update', $service);

        $image = ServiceImage::where('service_id', $service->id)->findOrFail($imageId);

        // Delete file from storage
        $oldPath = str_replace(asset('storage/'), '', $image->image_path);
        Storage::disk('public')->delete($oldPath);

        $image->delete();

        return response()->json([
            'message' => 'Foto berhasil dihapus.',
            'service' => $service->load('images')
        ]);
    }
}
