<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  array  ...$roles (Menerima multiple role_id, misal: 1, 2)
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek apakah pengguna sudah login
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // 2. Ambil role_id milik user yang sedang login
        $userRoleId = (string) $request->user()->role_id;

        // 3. Cek apakah role_id user ada di dalam daftar role yang diizinkan
        if (!in_array($userRoleId, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Anda tidak memiliki akses ke halaman/endpoint ini.'
            ], 403);
        }

        return $next($request);
    }
}