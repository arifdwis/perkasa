<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminStoreController extends Controller
{
    /**
     * List all stores (for Admin).
     */
    public function index(Request $request)
    {
        $query = Store::with(['alumniProfile.user']);

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by search keyword (Store Name or Owner Name)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('alumniProfile.user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $stores = $query->latest()->paginate(15);
        return response()->json($stores);
    }

    /**
     * Moderate a store (Approve or Suspend).
     */
    public function verify(Request $request, $id)
    {
        $store = Store::findOrFail($id);

        $request->validate([
            'action' => ['required', 'string', Rule::in(['approve', 'suspend'])],
            'reason' => ['nullable', 'string', 'max:500']
        ]);

        $status = 'pending';
        if ($request->action === 'approve') {
            $status = 'active';
            
            // Assign 'alumni_penjual' role to the owner user
            $user = $store->alumniProfile->user;
            if ($user && !$user->hasRole('alumni_penjual')) {
                $user->assignRole('alumni_penjual');
            }
        } elseif ($request->action === 'suspend') {
            $status = 'suspended';
        }

        $store->update([
            'status' => $status
        ]);

        // Log to Spatie Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($store)
            ->log("Mengubah status moderasi toko {$store->name} menjadi: {$status}." . ($request->reason ? " Alasan: {$request->reason}" : ""));

        return response()->json([
            'message' => "Status moderasi toko berhasil diperbarui menjadi {$status}.",
            'store' => $store->load(['alumniProfile.user'])
        ]);
    }
}
