<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KoperasiMember;
use App\Notifications\AlumniVerificationNotification;
use Illuminate\Http\Request;

/**
 * Admin validation of koperasi memberships.
 *
 * Reads from koperasi_members today. Once that table is merged into
 * alumni_profiles (koperasi.md section 5) this is the single place that has to
 * change its source — keep the queries here rather than spreading them around.
 */
class AdminKoperasiController extends Controller
{
    /**
     * List registrations, newest first.
     */
    public function index(Request $request)
    {
        $query = KoperasiMember::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Registrations that have / have not created their account yet
        if ($request->filled('activated')) {
            $request->boolean('activated')
                ? $query->whereNotNull('user_id')
                : $query->whereNull('user_id');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate(15));
    }

    /**
     * Detail of a single registration.
     */
    public function show($id)
    {
        $member = KoperasiMember::with(['user.profile', 'admin'])->findOrFail($id);

        return response()->json(['member' => $member]);
    }

    /**
     * Approve or reject a membership.
     */
    public function verify(Request $request, $id)
    {
        $member = KoperasiMember::with('user.profile')->findOrFail($id);

        $request->validate([
            'action' => ['required', 'string', 'in:approve,reject'],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ]);

        // Approving before activation would have nowhere to land: there is no
        // alumni profile to mark verified yet. Rejecting is still allowed —
        // a registration can be turned down before its owner ever activates.
        if ($request->action === 'approve' && ! $member->isActivated()) {
            return response()->json([
                'message' => 'Pendaftar belum membuat akun. Keanggotaan baru dapat disetujui setelah aktivasi selesai.',
            ], 422);
        }

        $status = $request->action === 'approve' ? 'approved' : 'rejected';

        $member->update([
            'status' => $status,
            'catatan_admin' => $request->reason,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        // Mirror the decision onto the alumni profile, which is where it will
        // live permanently once koperasi_members is merged away.
        $profile = $member->user?->profile;
        if ($profile) {
            $profile->update([
                'status_koperasi' => $status,
                'koperasi_approved_at' => now(),
                'status_verifikasi' => $status === 'approved' ? 'verified' : $profile->status_verifikasi,
                'badge_verified' => $status === 'approved' ? true : $profile->badge_verified,
            ]);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($member)
            ->log("Mengubah status keanggotaan koperasi {$member->name} menjadi: {$status}");

        if ($member->user) {
            try {
                $member->user->notify(new AlumniVerificationNotification(
                    $status === 'approved' ? 'verified' : 'rejected',
                    $request->reason
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => "Keanggotaan koperasi berhasil diperbarui menjadi {$status}.",
            'member' => $member->fresh(['user.profile']),
        ]);
    }
}
