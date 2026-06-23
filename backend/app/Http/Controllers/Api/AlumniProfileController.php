<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlumniProfileController extends Controller
{
    /**
     * Get the authenticated alumni's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load('profile.store');

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Update the authenticated alumni's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (! $profile) {
            return response()->json(['message' => 'Profil alumni tidak ditemukan.'], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'whatsapp' => ['sometimes', 'string', 'max:20'],
            'domisili' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ]);

        // Update user name
        if ($request->has('name')) {
            $user->update(['name' => $request->name]);
        }

        // Update profile fields
        $profile->update($request->only(['whatsapp', 'domisili', 'latitude', 'longitude']));

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user->fresh()->load('profile.store'),
        ]);
    }

    /**
     * Upload / replace profile photo.
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $profile = $user->profile;

        if (! $profile) {
            return response()->json(['message' => 'Profil alumni tidak ditemukan.'], 404);
        }

        // Delete old photo
        if ($profile->foto_profil) {
            Storage::disk('public')->delete($profile->foto_profil);
        }

        $path = $request->file('photo')->store('alumni/photos', 'public');
        $profile->update(['foto_profil' => $path]);

        return response()->json([
            'message' => 'Foto profil berhasil diperbarui.',
            'foto_profil' => Storage::disk('public')->url($path),
            'user' => $user->fresh()->load('profile.store'),
        ]);
    }
}
