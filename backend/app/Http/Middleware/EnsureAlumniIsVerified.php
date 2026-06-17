<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAlumniIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super Admin bypasses verification checks
        if ($user && $user->hasRole('super_admin')) {
            return $next($request);
        }

        if (!$user || !$user->profile || $user->profile->status_verifikasi !== 'verified') {
            return response()->json([
                'message' => 'Akses ditolak. Akun alumni Anda belum diverifikasi oleh admin.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
