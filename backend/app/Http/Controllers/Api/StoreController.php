<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    /**
     * Apply for a new store (Verified Alumni only).
     */
    public function register(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile) {
            return response()->json([
                'message' => 'Profil alumni tidak ditemukan.',
            ], 400);
        }

        // Only verified alumni can register a store
        if ($profile->status_verifikasi !== 'verified') {
            return response()->json([
                'message' => 'Hanya alumni terverifikasi yang dapat membuka toko.',
            ], 403);
        }

        // Limit to 1 store per alumni
        if ($profile->store) {
            return response()->json([
                'message' => 'Alumni hanya diperbolehkan memiliki satu toko.',
            ], 400);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'kategori_usaha' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'kota' => ['required', 'string', 'max:255'],
            'kecamatan' => ['nullable', 'string', 'max:255'],
            'kelurahan' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'tahun_berdiri' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'delivery_type' => ['required', 'string', Rule::in(['fixed', 'per_wilayah'])],
            'fixed_delivery_fee' => ['required_if:delivery_type,fixed', 'numeric', 'min:0', 'nullable'],
            'delivery_fees' => ['required_if:delivery_type,per_wilayah', 'array', 'nullable'],
            'delivery_fees.*.wilayah' => ['required_with:delivery_fees', 'string', 'max:255'],
            'delivery_fees.*.fee' => ['required_with:delivery_fees', 'numeric', 'min:0'],
        ]);

        $store = DB::transaction(function () use ($request, $profile) {
            $store = Store::create([
                'alumni_profile_id' => $profile->id,
                'name' => $request->name,
                'description' => $request->description,
                'kategori_usaha' => $request->kategori_usaha,
                'whatsapp' => $request->whatsapp,
                'kota' => $request->kota,
                'kecamatan' => $request->kecamatan,
                'kelurahan' => $request->kelurahan,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'tahun_berdiri' => $request->tahun_berdiri,
                'status' => 'pending', // Starts as pending
                'delivery_type' => $request->delivery_type,
                'fixed_delivery_fee' => $request->delivery_type === 'fixed' ? $request->fixed_delivery_fee : 0.00,
            ]);

            if ($request->delivery_type === 'per_wilayah' && $request->has('delivery_fees')) {
                foreach ($request->delivery_fees as $feeItem) {
                    $store->deliveryFees()->create([
                        'wilayah' => $feeItem['wilayah'],
                        'fee' => $feeItem['fee'],
                    ]);
                }
            }

            return $store;
        });

        // Log to Spatie Activity Log
        activity()
            ->causedBy($request->user())
            ->performedOn($store)
            ->log("Mengajukan pendaftaran toko baru bernama: {$store->name}");

        return response()->json([
            'message' => 'Pendaftaran toko berhasil diajukan. Menunggu verifikasi admin.',
            'store' => $store->load('deliveryFees'),
        ], 201);
    }

    /**
     * Get owner's store.
     */
    public function myStore(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json([
                'store' => null,
            ]);
        }

        $store = $profile->store->load('deliveryFees');

        return response()->json([
            'store' => $store,
        ]);
    }

    /**
     * Update owner's store settings.
     */
    public function updateMyStore(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json([
                'message' => 'Toko tidak ditemukan.',
            ], 404);
        }

        $store = $profile->store;

        // Authorize with policy
        Gate::authorize('update', $store);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'kategori_usaha' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'kota' => ['required', 'string', 'max:255'],
            'kecamatan' => ['nullable', 'string', 'max:255'],
            'kelurahan' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'tahun_berdiri' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'delivery_type' => ['required', 'string', Rule::in(['fixed', 'per_wilayah'])],
            'fixed_delivery_fee' => ['required_if:delivery_type,fixed', 'numeric', 'min:0', 'nullable'],
            'delivery_fees' => ['required_if:delivery_type,per_wilayah', 'array', 'nullable'],
            'delivery_fees.*.wilayah' => ['required_with:delivery_fees', 'string', 'max:255'],
            'delivery_fees.*.fee' => ['required_with:delivery_fees', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $store) {
            $store->update([
                'name' => $request->name,
                'description' => $request->description,
                'kategori_usaha' => $request->kategori_usaha,
                'whatsapp' => $request->whatsapp,
                'kota' => $request->kota,
                'kecamatan' => $request->kecamatan,
                'kelurahan' => $request->kelurahan,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'tahun_berdiri' => $request->tahun_berdiri,
                'delivery_type' => $request->delivery_type,
                'fixed_delivery_fee' => $request->delivery_type === 'fixed' ? $request->fixed_delivery_fee : 0.00,
            ]);

            // Sync delivery fees
            $store->deliveryFees()->delete();
            if ($request->delivery_type === 'per_wilayah' && $request->has('delivery_fees')) {
                foreach ($request->delivery_fees as $feeItem) {
                    $store->deliveryFees()->create([
                        'wilayah' => $feeItem['wilayah'],
                        'fee' => $feeItem['fee'],
                    ]);
                }
            }
        });

        // Log to Spatie Activity Log
        activity()
            ->causedBy($request->user())
            ->performedOn($store)
            ->log("Memperbarui detail profil toko: {$store->name}");

        return response()->json([
            'message' => 'Profil toko berhasil diperbarui.',
            'store' => $store->fresh()->load('deliveryFees'),
        ]);
    }

    /**
     * Upload logo for store.
     */
    public function uploadLogo(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json(['message' => 'Toko tidak ditemukan.'], 404);
        }

        $store = $profile->store;
        Gate::authorize('update', $store);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB max
        ]);

        if ($store->logo) {
            // Delete old file
            $oldPath = str_replace(asset('storage/'), '', $store->logo);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('logo')->store('store_logos', 'public');
        $store->update([
            'logo' => asset('storage/'.$path),
        ]);

        return response()->json([
            'message' => 'Logo toko berhasil diperbarui.',
            'logo' => $store->logo,
        ]);
    }

    /**
     * Upload banner for store.
     */
    public function uploadBanner(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json(['message' => 'Toko tidak ditemukan.'], 404);
        }

        $store = $profile->store;
        Gate::authorize('update', $store);

        $request->validate([
            'banner' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'], // 2MB max
        ]);

        if ($store->banner) {
            // Delete old file
            $oldPath = str_replace(asset('storage/'), '', $store->banner);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('banner')->store('store_banners', 'public');
        $store->update([
            'banner' => asset('storage/'.$path),
        ]);

        return response()->json([
            'message' => 'Banner toko berhasil diperbarui.',
            'banner' => $store->banner,
        ]);
    }

    /**
     * Close (nonaktifkan) owner's store.
     */
    public function closeMyStore(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json([
                'message' => 'Toko tidak ditemukan.',
            ], 404);
        }

        $store = $profile->store;
        Gate::authorize('update', $store);

        if ($store->status !== 'active') {
            return response()->json([
                'message' => 'Toko sudah tidak aktif.',
            ], 400);
        }

        $store->update(['status' => 'closed']);

        activity()
            ->causedBy($request->user())
            ->performedOn($store)
            ->log("Menutup toko: {$store->name}");

        return response()->json([
            'message' => 'Toko berhasil ditutup. Anda dapat membuka kembali kapan saja.',
            'store' => $store->fresh()->load('deliveryFees'),
        ]);
    }

    /**
     * Reopen owner's store.
     */
    public function reopenMyStore(Request $request)
    {
        $profile = $request->user()->profile;
        if (! $profile || ! $profile->store) {
            return response()->json([
                'message' => 'Toko tidak ditemukan.',
            ], 404);
        }

        $store = $profile->store;
        Gate::authorize('update', $store);

        if ($store->status !== 'closed') {
            return response()->json([
                'message' => 'Toko tidak dalam status tertutup.',
            ], 400);
        }

        $store->update(['status' => 'active']);

        activity()
            ->causedBy($request->user())
            ->performedOn($store)
            ->log("Membuka kembali toko: {$store->name}");

        return response()->json([
            'message' => 'Toko berhasil dibuka kembali.',
            'store' => $store->fresh()->load('deliveryFees'),
        ]);
    }

    /**
     * View public store profile.
     */
    public function show($id)
    {
        $store = Store::with(['alumniProfile.user', 'deliveryFees'])->findOrFail($id);

        // If the store is not active, only owners or admins can view it
        if ($store->status !== 'active') {
            $user = auth('sanctum')->user();
            if (! $user) {
                return response()->json(['message' => 'Akses ditolak. Toko belum aktif.'], 403);
            }

            $isOwner = $store->alumniProfile->user_id === $user->id;
            $isAdmin = $user->hasRole('super_admin') || $user->hasRole('admin_marketplace');

            if (! $isOwner && ! $isAdmin) {
                return response()->json(['message' => 'Akses ditolak. Toko belum aktif.'], 403);
            }
        }

        return response()->json([
            'store' => $store,
        ]);
    }
}
