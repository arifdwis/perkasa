<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->hasRole('super_admin') && ! $user->hasRole('admin_marketplace'))) {
            return response()->json([
                'message' => 'Akses ditolak. Rute ini hanya dapat diakses oleh Admin.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
