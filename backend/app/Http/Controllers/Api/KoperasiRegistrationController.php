<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlumniProfile;
use App\Models\KoperasiMember;
use App\Models\User;
use App\Notifications\AlumniRegisteredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Koperasi membership registration — a three step public flow.
 *
 *   1. store()     POST /koperasi/register   personal data, no credentials
 *   2. cek()       POST /koperasi/cek        find the registration by NIM + nama
 *   3. buatAkun()  POST /koperasi/buat-akun  set username + password, become an alumni
 *
 * Step 2 does not follow automatically from step 1 — the registrant opens the
 * activation page themselves and identifies with their NIM and name.
 */
class KoperasiRegistrationController extends Controller
{
    /**
     * How long the token issued by cek() stays usable.
     */
    private const TOKEN_TTL_MINUTES = 15;

    private const TOKEN_CACHE_PREFIX = 'koperasi_aktivasi:';

    /**
     * Step 1 — record the applicant's personal data.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:koperasi_members,email', 'unique:users,email'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'nim' => ['required', 'string', 'max:50', 'unique:koperasi_members,nim', 'unique:alumni_profiles,nim'],
            'program_studi' => ['required', 'string', 'max:255'],
            'tahun_masuk' => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
            'tahun_lulus' => ['required', 'integer', 'min:1950', 'max:'.(date('Y') + 5), 'gte:tahun_masuk'],
        ], [
            'tahun_lulus.gte' => 'Tahun lulus harus sama dengan atau setelah tahun masuk.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'nim.unique' => 'NIM ini sudah terdaftar.',
        ]);

        KoperasiMember::create([
            ...$validated,
            'name' => trim($validated['name']),
            'nim' => trim($validated['nim']),
            'email' => trim($validated['email']),
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Pendaftaran tersimpan. Silakan aktifkan akun Anda di halaman aktivasi.',
        ], 201);
    }

    /**
     * Step 2 — look the registration up by NIM + nama.
     *
     * Both must match; either one alone is not enough. On success a short lived
     * single use token is issued, which step 3 requires.
     */
    public function cek(Request $request)
    {
        $request->validate([
            'nim' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $member = KoperasiMember::whereRaw('LOWER(nim) = ?', [mb_strtolower(trim($request->nim))])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($request->name))])
            ->whereNull('user_id')
            ->first();

        // Same response whether the registration never existed or was already
        // activated — so this endpoint cannot be used to probe membership.
        if (! $member) {
            return response()->json([
                'found' => false,
                'message' => 'Data pendaftaran tidak ditemukan. Periksa kembali NIM dan nama Anda.',
            ], 404);
        }

        $token = Str::random(64);
        Cache::put(self::TOKEN_CACHE_PREFIX.$token, $member->id, now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return response()->json([
            'found' => true,
            'token' => $token,
            'expires_in' => self::TOKEN_TTL_MINUTES * 60,
            'name' => $member->name,
            'nim' => $member->nim,
            'email' => $member->email,
        ]);
    }

    /**
     * Step 3 — set the credentials and promote the registration into a real
     * account. This is where the applicant "otomatis jadi data alumni".
     */
    public function buatAkun(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'username' => ['required', 'string', 'min:4', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', 'unique:users,username'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan garis bawah.',
            'username.unique' => 'Username ini sudah dipakai. Pilih yang lain.',
        ]);

        $cacheKey = self::TOKEN_CACHE_PREFIX.$request->token;
        $memberId = Cache::get($cacheKey);

        if (! $memberId) {
            return response()->json([
                'message' => 'Sesi aktivasi sudah kedaluwarsa. Silakan cari kembali data pendaftaran Anda.',
            ], 422);
        }

        $member = KoperasiMember::find($memberId);

        if (! $member || $member->isActivated()) {
            Cache::forget($cacheKey);

            return response()->json([
                'message' => 'Pendaftaran ini sudah diaktifkan sebelumnya.',
            ], 422);
        }

        $user = DB::transaction(function () use ($request, $member) {
            $user = User::create([
                'name' => $member->name,
                'username' => $request->username,
                'email' => $member->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole('alumni_pembeli');

            AlumniProfile::create([
                'user_id' => $user->id,
                'nim' => $member->nim,
                'program_studi' => $member->program_studi,
                'tahun_masuk' => $member->tahun_masuk,
                'tahun_lulus' => $member->tahun_lulus,
                'whatsapp' => $member->whatsapp,
                'status_verifikasi' => 'pending',
                'badge_verified' => false,
                'is_koperasi_member' => true,
                'status_koperasi' => 'pending',
            ]);

            $member->update(['user_id' => $user->id]);

            return $user;
        });

        // Single use — the token dies with the account it created.
        Cache::forget($cacheKey);

        // Outside the transaction, and swallowed on failure: a broken
        // notification channel must not read as a failed registration.
        try {
            $user->notify(new AlumniRegisteredNotification);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => 'Akun berhasil dibuat. Silakan masuk. Keanggotaan koperasi Anda menunggu validasi admin.',
            'user' => $user->only(['id', 'name', 'username', 'email']),
        ], 201);
    }
}
