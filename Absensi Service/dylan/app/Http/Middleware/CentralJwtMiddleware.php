<?php

namespace App\Http\Middleware;

use App\Services\JwksJwtVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CentralJwtMiddleware
{
    public function __construct(private readonly JwksJwtVerifier $jwtVerifier)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $authorization = (string) $request->header('Authorization', '');
        if (!str_starts_with($authorization, 'Bearer ')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Bearer token SSO wajib dikirim.',
                'errors' => null,
            ], 401);
        }

        try {
            $claims = $this->jwtVerifier->verify(trim(substr($authorization, 7)));
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: JWT SSO tidak valid.',
                'errors' => $e->getMessage(),
            ], 401);
        }

        if (($claims['token_type'] ?? null) !== 'user') {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: transaksi absensi harus memakai token user SSO, bukan token M2M.',
                'errors' => null,
            ], 403);
        }

        $profile = $claims['profile'] ?? [];
        $email = $profile['email'] ?? $claims['sub'] ?? null;
        $localRoles = config('iae.local_roles', []);
        $role = $localRoles[$email] ?? 'guest';

        if (!in_array($role, config('iae.allowed_attendance_roles', []), true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: user SSO belum punya role lokal untuk mencatat absensi.',
                'errors' => ['role' => $role],
            ], 403);
        }

        $request->attributes->set('central_claims', $claims);
        $request->attributes->set('central_user', [
            'email' => $email,
            'name' => $profile['name'] ?? $email,
            'nim' => $profile['nim'] ?? null,
        ]);
        $request->attributes->set('central_role', $role);

        return $next($request);
    }
}
