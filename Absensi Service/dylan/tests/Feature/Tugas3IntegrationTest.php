<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Tugas3IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_login_proxy_returns_token_response(): void
    {
        config(['iae.central_base_url' => 'https://iae-sso.test']);

        Http::fake([
            'https://iae-sso.test/api/v1/auth/token' => Http::response([
                'status' => 'success',
                'token_type' => 'user',
                'token' => 'jwt-from-sso',
                'profile' => [
                    'email' => 'warga24@ktp.iae.id',
                    'name' => 'Xavier Gunawan',
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/tugas-3/sso/login', [
            'email' => 'warga24@ktp.iae.id',
            'password' => 'KtpDigital2026!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.token', 'jwt-from-sso');
    }

    public function test_tugas3_attendance_requires_bearer_jwt(): void
    {
        $response = $this->postJson('/api/v1/tugas-3/attendances', [
            'employee_id' => 'EMP-001',
            'date' => '2026-06-12',
            'status' => 'hadir',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }
}
