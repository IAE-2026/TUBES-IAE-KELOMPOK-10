<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CentralAuthService
{
    public function loginUser(string $email, string $password): array
    {
        $response = Http::asJson()
            ->timeout(15)
            ->post($this->url('/api/v1/auth/token'), [
                'email'    => $email,
                'password' => $password,
            ]);

        if (!$response->successful()) {
            $msg = $response->json('message') ?? $response->json('error') ?? 'HTTP ' . $response->status();
            throw new RuntimeException('Login ke SSO dosen gagal: ' . $msg);
        }

        $body = $response->json();

        if (!is_array($body)) {
            throw new RuntimeException('Response login SSO bukan JSON valid.');
        }

        return $body;
    }

    public function getMachineToken(): string
    {
        $cached = Cache::get('iae.central.m2m_token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::asJson()
            ->timeout(15)
            ->post($this->url('/api/v1/auth/token'), [
                'api_key' => config('iae.central_api_key'),
                'nim'     => config('iae.nim'),
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gagal mengambil token M2M dari SSO dosen.');
        }

        $body = $response->json();
        $token = $body['token'] ?? $body['access_token'] ?? null;

        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Response token M2M tidak berisi field token.');
        }

        $expiresIn = (int) ($body['expires_in'] ?? 3600);
        $margin = (int) config('iae.token_cache_seconds_margin', 60);
        Cache::put('iae.central.m2m_token', $token, max(60, $expiresIn - $margin));

        return $token;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('iae.central_base_url'), '/') . $path;
    }
}
