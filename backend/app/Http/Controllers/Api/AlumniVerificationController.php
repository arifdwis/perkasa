<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\AlumniImport;
use App\Models\AlumniProfile;
use App\Models\AlumniVerification;
use App\Notifications\AlumniVerificationNotification;
use App\Services\WebPushService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class AlumniVerificationController extends Controller
{
    /**
     * Import official alumni records via Excel/CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'], // max 5MB
        ]);

        try {
            Excel::import(new AlumniImport, $request->file('file'));

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->log('Mengimpor database resmi alumni via berkas Excel/CSV');

            return response()->json([
                'message' => 'Data alumni resmi berhasil di-import.',
            ]);
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: ".implode(', ', $failure->errors());
            }

            return response()->json([
                'message' => 'Gagal mengimpor data. Terdapat kesalahan validasi.',
                'errors' => array_slice($errorMessages, 0, 10), // Limit to top 10 errors
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memproses file. Pastikan format kolom sesuai: nim, nama, program_studi, tahun_masuk, tahun_lulus, email, whatsapp.',
            ], 500);
        }
    }

    /**
     * List all registered alumni profiles (with filtering).
     */
    public function index(Request $request)
    {
        $query = AlumniProfile::with(['user', 'user.roles']);

        // Filter by verification status
        if ($request->has('status') && ! empty($request->status)) {
            $query->where('status_verifikasi', $request->status);
        }

        // Filter by keyword (Name or NIM)
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $profiles = $query->latest()->paginate(15);

        return response()->json($profiles);
    }

    /**
     * Get detailed view of an alumni profile.
     */
    public function show($id)
    {
        $profile = AlumniProfile::with(['user', 'user.roles'])->findOrFail($id);

        // Load verification history
        $history = AlumniVerification::with('admin')
            ->where('alumni_profile_id', $profile->id)
            ->latest()
            ->get();

        return response()->json([
            'profile' => $profile,
            'history' => $history,
        ]);
    }

    /**
     * Verify, reject, or suspend an alumni account.
     */
    public function verify(Request $request, $id)
    {
        $profile = AlumniProfile::findOrFail($id);

        $request->validate([
            'action' => ['required', 'string', 'in:approve,reject,suspend'],
            'reason' => ['required_if:action,reject,suspend', 'string', 'nullable', 'max:500'],
        ]);

        $status = 'pending';
        $badge = false;

        switch ($request->action) {
            case 'approve':
                $status = 'verified';
                $badge = true;
                break;
            case 'reject':
                $status = 'rejected';
                $badge = false;
                break;
            case 'suspend':
                $status = 'suspended';
                $badge = false;
                break;
        }

        // Update profile verification status
        $profile->update([
            'status_verifikasi' => $status,
            'badge_verified' => $badge,
        ]);

        // If verified, we can promote role to alumni_penjual if necessary,
        // or just let them stay alumni_pembeli until they apply for store (which is checked in plan.md)

        // Create verification log entry
        AlumniVerification::create([
            'alumni_profile_id' => $profile->id,
            'admin_user_id' => auth()->id(),
            'action' => $request->action,
            'reason' => $request->reason,
        ]);

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($profile->user)
            ->log("Mengubah status verifikasi alumni {$profile->user->name} menjadi: {$status}");

        // Send notification (database + email)
        $profile->user->notify(new AlumniVerificationNotification($status, $request->reason));
        $title = $status === 'verified' ? 'Akun Terverifikasi!' : 'Status Akun: ' . ucfirst($status);
        $body = $status === 'verified'
            ? 'Akun alumni Anda sudah terverifikasi. Selamat berbelanja di Perkasa FEB Unmul!'
            : 'Status verifikasi alumni Anda: ' . $status . '. Hubungi admin jika ada pertanyaan.';
        app(WebPushService::class)->sendToUser($profile->user->id, $title, $body, '/logo_unmul.png', '/buyer/home');

        return response()->json([
            'message' => "Status verifikasi alumni berhasil diperbarui menjadi {$status}.",
            'profile' => $profile->load('user'),
        ]);
    }
}
