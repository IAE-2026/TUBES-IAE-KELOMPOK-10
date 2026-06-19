<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * HTTP client untuk mengambil rekap absensi dari Service B (Dylan - 102022400074).
 * Dipanggil saat Payroll Service menjalankan proses payroll otomatis.
 */
class AbsensiServiceClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('ABSENSI_SERVICE_URL', 'http://absensi-service:80'), '/');
        $this->apiKey  = env('ABSENSI_SERVICE_KEY', '102022400074');
    }

    /**
     * Ambil ringkasan absensi bulanan satu karyawan.
     * Memanggil: GET /api/v1/attendances/summary/{employeeId}/{year}/{month}
     *
     * @return array{success: bool, status_code: int, data: array|null, error: string|null}
     */
    public function getMonthlySummary(string $nip, int $year, int $month): array
    {
        try {
            $response = Http::connectTimeout(10)
                ->timeout(30)
                ->withHeaders([
                    'X-IAE-KEY' => $this->apiKey,
                    'Accept'    => 'application/json',
                ])
                ->get("{$this->baseUrl}/api/v1/attendances/summary/{$nip}/{$year}/{$month}");

            return [
                'success'     => $response->successful(),
                'status_code' => $response->status(),
                'data'        => $response->json('data'),
                'error'       => $response->successful() ? null : $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'success'     => false,
                'status_code' => 503,
                'data'        => null,
                'error'       => "Absensi Service tidak dapat dihubungi: {$e->getMessage()}",
            ];
        }
    }
}
