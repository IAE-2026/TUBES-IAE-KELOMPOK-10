<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * HTTP client untuk mengambil data karyawan dari Service A (Dimas - 102022400197).
 * Dipanggil saat Payroll Service menjalankan proses payroll otomatis.
 */
class EmployeeServiceClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('EMPLOYEE_SERVICE_URL', 'http://employee-service:8000'), '/');
        $this->apiKey  = env('EMPLOYEE_SERVICE_KEY', '102022400197');
    }

    /**
     * Ambil data karyawan berdasarkan employee_id / NIP.
     * Memanggil: GET /api/v1/employees/{nip}
     * (Dimas' show() mendukung lookup by employee_id string maupun numeric id)
     *
     * @return array{success: bool, status_code: int, data: array|null, error: string|null}
     */
    public function getByNip(string $nip): array
    {
        try {
            $response = Http::connectTimeout(10)
                ->timeout(30)
                ->withHeaders([
                    'X-IAE-KEY' => $this->apiKey,
                    'Accept'    => 'application/json',
                ])
                ->get("{$this->baseUrl}/api/v1/employees/{$nip}");

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
                'error'       => "Employee Service tidak dapat dihubungi: {$e->getMessage()}",
            ];
        }
    }
}
