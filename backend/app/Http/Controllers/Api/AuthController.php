<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportedAlumniRecord;
use App\Models\User;
use App\Notifications\AlumniRegisteredNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new alumni user.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', Password::min(8)],
            'nim' => ['required', 'string', 'max:50', 'unique:alumni_profiles,nim'],
            'program_studi' => ['required', 'string', 'max:255'],
            'tahun_masuk' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'tahun_lulus' => ['required', 'integer', 'min:1900', 'max:'.(date('Y') + 5)],
        ]);

        // Check if user is in imported records for auto-verification
        $importedRecord = ImportedAlumniRecord::where('nim', $request->nim)->first();

        $isAutoVerified = false;
        if ($importedRecord && strtolower(trim($importedRecord->email)) === strtolower(trim($request->email))) {
            $isAutoVerified = true;
        }

        $user = DB::transaction(function () use ($request, $isAutoVerified) {
            // Create user credentials
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign default role (alumni_pembeli)
            $user->assignRole('alumni_pembeli');

            // Create alumni profile
            $user->profile()->create([
                'nim' => $request->nim,
                'program_studi' => $request->program_studi,
                'tahun_masuk' => $request->tahun_masuk,
                'tahun_lulus' => $request->tahun_lulus,
                'whatsapp' => $request->whatsapp,
                'status_verifikasi' => $isAutoVerified ? 'verified' : 'pending',
                'badge_verified' => $isAutoVerified,
            ]);

            return $user;
        });

        // Trigger Registered event to auto-send Laravel verification email
        event(new Registered($user));

        // Send database notification
        $user->notify(new AlumniRegisteredNotification);

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan cek email Anda untuk melakukan verifikasi. Akun Anda sedang menunggu verifikasi data Perkasa.',
            'user' => $user->load('profile'),
        ], 201);
    }

    /**
     * Authenticate user and generate Sanctum token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // Check if user is suspended
        if ($user->profile && $user->profile->status_verifikasi === 'suspended') {
            Auth::logout();

            return response()->json([
                'message' => 'Akses diblokir. Akun alumni Anda ditangguhkan (suspended). Hubungi admin.',
            ], 403);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Get effective permissions
        $permissions = $user->getAllPermissions()->pluck('name');

        // Bypassing check if super_admin
        if ($user->hasRole('super_admin')) {
            $permissions->push('super_admin');
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load(['profile.store', 'roles']),
            'permissions' => $permissions,
        ]);
    }

    /**
     * Revoke current user session token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil keluar log (logout).',
        ]);
    }

    /**
     * Return authenticated user profile and permissions.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $permissions = $user->getAllPermissions()->pluck('name');
        if ($user->hasRole('super_admin')) {
            $permissions->push('super_admin');
        }

        return response()->json([
            'user' => $user->load(['profile.store', 'roles']),
            'permissions' => $permissions,
        ]);
    }

    /**
     * Verify email via API endpoint link.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Tautan verifikasi tidak valid atau kedaluwarsa.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email Anda sudah terverifikasi sebelumnya.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email Anda berhasil diverifikasi.']);
    }

    /**
     * Resend verification notification.
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email Anda sudah terverifikasi.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Tautan verifikasi email baru telah dikirim ke alamat email Anda.',
        ]);
    }
}
