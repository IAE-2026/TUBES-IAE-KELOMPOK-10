<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CentralAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CentralAuthController extends Controller
{
    public function login(Request $request, CentralAuthService $authService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $ssoResponse = $authService->loginUser(
                (string) $request->input('email'),
                (string) $request->input('password')
            );
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login ke SSO dosen gagal.',
                'errors' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login SSO berhasil. Token ini dipakai sebagai Bearer token untuk endpoint Tugas 3.',
            'data' => $ssoResponse,
        ]);
    }
}
