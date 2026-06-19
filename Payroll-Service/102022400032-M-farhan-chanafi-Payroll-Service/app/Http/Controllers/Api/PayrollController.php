<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollSlip;
use App\Services\AbsensiServiceClient;
use App\Services\EmployeeServiceClient;
use App\Services\IaeCloudSsoService;
use App\Services\IaeRabbitMqPublisherService;
use App\Services\IaeSoapAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Payroll Service API",
 *     version="1.0.0",
 *     description="Dokumentasi API untuk Payroll Service pada tugas IAE"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="iaeKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-IAE-KEY"
 * )
 */
class PayrollController extends Controller
{
    private function successResponse($message, $data = null, $code = 200)
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'meta'    => ['service_name' => 'Payroll-Service', 'api_version' => 'v1'],
        ], $code);
    }

    private function errorResponse($message, $errors = null, $code = 400)
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payroll-slips",
     *     summary="Menampilkan seluruh slip gaji",
     *     tags={"Payroll"},
     *     security={{"iaeKey":{}}},
     *     @OA\Response(response=200, description="Payroll slips retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index()
    {
        return $this->successResponse(
            'Payroll slips retrieved successfully',
            PayrollSlip::orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->get()
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payroll-slips/{nip}/{tahun}/{bulan}",
     *     summary="Menampilkan detail slip gaji berdasarkan NIP, tahun, dan bulan",
     *     tags={"Payroll"},
     *     security={{"iaeKey":{}}},
     *     @OA\Parameter(name="nip",   in="path", required=true, @OA\Schema(type="string",  example="EMP001")),
     *     @OA\Parameter(name="tahun", in="path", required=true, @OA\Schema(type="integer", example=2026)),
     *     @OA\Parameter(name="bulan", in="path", required=true, @OA\Schema(type="integer", example=5)),
     *     @OA\Response(response=200, description="Payroll slip retrieved successfully"),
     *     @OA\Response(response=404, description="Payroll slip not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function showByPeriod($nip, $tahun, $bulan)
    {
        $payrollSlip = PayrollSlip::where('nip', $nip)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        if (! $payrollSlip) {
            return $this->errorResponse('Payroll slip not found', null, 404);
        }

        return $this->successResponse('Payroll slip retrieved successfully', $payrollSlip);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payroll-runs",
     *     summary="Menjalankan proses payroll bulanan (End-to-End otomatis)",
     *     description="Sistem mengambil data karyawan dari Employee Service dan rekap kehadiran dari Absensi Service secara otomatis, lalu menghitung dan menyimpan slip gaji. Diakhiri dengan SSO → SOAP Audit → RabbitMQ broadcast.",
     *     tags={"Payroll"},
     *     security={{"iaeKey":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nip","tahun","bulan"},
     *             @OA\Property(property="nip",   type="string",  example="EMP001",
     *                          description="employee_id karyawan (sama dengan di Employee Service dan Absensi Service)"),
     *             @OA\Property(property="tahun", type="integer", example=2026),
     *             @OA\Property(property="bulan", type="integer", example=5, minimum=1, maximum=12)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Payroll processed successfully"),
     *     @OA\Response(response=422, description="Validation failed"),
     *     @OA\Response(response=502, description="Employee/Absensi Service tidak dapat dihubungi"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     *
     * Alur end-to-end:
     *   1. Validasi input (nip, tahun, bulan)
     *   2. Ambil data karyawan dari Employee Service (Dimas)
     *      → GET /api/v1/employees/{nip} → name, base_salary, fixed_allowance
     *   3. Ambil rekap absensi dari Absensi Service (Dylan)
     *      → GET /api/v1/attendances/summary/{nip}/{tahun}/{bulan}
     *      → jumlah hadir/izin/sakit/alpha
     *   4. Hitung gaji: total = base_salary + fixed_allowance - (alpha × Rp100.000)
     *   5. SSO Login → dapat JWT
     *   6. SOAP Audit → dapat ReceiptNumber
     *   7. RabbitMQ Broadcast → event published
     */
    public function runPayroll(
        Request $request,
        EmployeeServiceClient $empClient,
        AbsensiServiceClient $absClient,
        IaeCloudSsoService $ssoService,
        IaeSoapAuditService $soapAuditService,
        IaeRabbitMqPublisherService $publisherService
    ) {
        // ── 1. Validasi input ─────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'nip'   => 'required|string|max:50',
            'tahun' => 'required|integer|min:2020|max:2099',
            'bulan' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $nip   = $request->nip;
        $tahun = (int) $request->tahun;
        $bulan = (int) $request->bulan;

        // ── 2. Ambil data karyawan dari Service A (Dimas) ─────────────────────
        $empResult = $empClient->getByNip($nip);
        if (! $empResult['success']) {
            return $this->errorResponse(
                "Gagal mengambil data karyawan '{$nip}' dari Employee Service.",
                ['employee_service_error' => $empResult['error'],
                 'status_code'           => $empResult['status_code']],
                502
            );
        }
        $emp = $empResult['data'];

        // Pastikan karyawan aktif (status 'active')
        if (strtolower($emp['status'] ?? '') !== 'active') {
            return $this->errorResponse(
                "Karyawan '{$nip}' tidak aktif (status: {$emp['status']}). Payroll dibatalkan.",
                null, 422
            );
        }

        // ── 3. Ambil rekap absensi dari Service B (Dylan) ─────────────────────
        $absResult = $absClient->getMonthlySummary($nip, $tahun, $bulan);
        if (! $absResult['success']) {
            return $this->errorResponse(
                "Gagal mengambil rekap absensi '{$nip}' bulan {$bulan}/{$tahun} dari Absensi Service.",
                ['absensi_service_error' => $absResult['error'],
                 'status_code'          => $absResult['status_code']],
                502
            );
        }
        $abs = $absResult['data'];

        // ── 4. SSO Login → JWT ────────────────────────────────────────────────
        $tokenResponse = $ssoService->getTokenByApiKey();
        if (! $tokenResponse['success']) {
            return $this->errorResponse(
                'Failed to get token from IAE Cloud SSO',
                $tokenResponse['body'],
                $tokenResponse['status_code']
            );
        }
        $token = $ssoService->extractToken($tokenResponse['body']);
        if (! $token) {
            return $this->errorResponse('Token was not found in SSO response', $tokenResponse['body'], 500);
        }

        $payload   = $ssoService->decodeJwtPayload($token);
        $localUser = $ssoService->mapPayloadToLocalRole($payload);

        if (($localUser['local_role'] ?? null) !== 'HR_ADMIN') {
            return $this->errorResponse('User does not have permission to run payroll', $localUser, 403);
        }

        // ── 5. Hitung gaji ────────────────────────────────────────────────────
        $gajiPokok      = (float) ($emp['base_salary']      ?? 0);
        $tunjanganTetap = (float) ($emp['fixed_allowance']   ?? 0);
        $jumlahAlpha    = (int)   ($abs['jumlah_alpha']      ?? 0);
        $potonganAbsensi = $jumlahAlpha * 100_000;          // Rp100.000 per hari alpha
        $totalGaji       = $gajiPokok + $tunjanganTetap - $potonganAbsensi;

        $payrollSlip = PayrollSlip::updateOrCreate(
            ['nip' => $nip, 'tahun' => $tahun, 'bulan' => $bulan],
            [
                'employee_name'    => $emp['name'],
                'gaji_pokok'       => $gajiPokok,
                'tunjangan_tetap'  => $tunjanganTetap,
                'jumlah_hadir'     => $abs['jumlah_hadir']  ?? 0,
                'jumlah_izin'      => $abs['jumlah_izin']   ?? 0,
                'jumlah_sakit'     => $abs['jumlah_sakit']  ?? 0,
                'jumlah_alpha'     => $jumlahAlpha,
                'potongan_absensi' => $potonganAbsensi,
                'total_gaji'       => $totalGaji,
                'status'           => 'Selesai',
            ]
        );

        // ── 6. SOAP Audit ─────────────────────────────────────────────────────
        $auditPayload = [
            'activity_name' => 'PayrollRunCreated',
            'log_content'   => [
                'service'     => 'Payroll-Service',
                'activity'    => 'PayrollRunCreated',
                'subject'     => $localUser['subject']    ?? null,
                'local_role'  => $localUser['local_role'] ?? null,
                'endpoint'    => 'POST /api/v1/payroll-runs',
                'nip'         => $payrollSlip->nip,
                'tahun'       => $payrollSlip->tahun,
                'bulan'       => $payrollSlip->bulan,
                'total_gaji'  => $payrollSlip->total_gaji,
                'status'      => $payrollSlip->status,
                'sources'     => [
                    'employee_service' => env('EMPLOYEE_SERVICE_URL', 'http://employee-service:8000'),
                    'absensi_service'  => env('ABSENSI_SERVICE_URL', 'http://absensi-service:80'),
                ],
            ],
        ];

        $auditResponse = $soapAuditService->sendAudit($token, $auditPayload);
        if (! $auditResponse['success']) {
            return $this->errorResponse(
                'Failed to send SOAP audit to IAE Cloud',
                ['status_code' => $auditResponse['status_code'], 'raw_response' => $auditResponse['raw_response']],
                $auditResponse['status_code']
            );
        }

        $payrollSlip->soap_receipt_number = $auditResponse['receipt_number'];
        $payrollSlip->save();

        // ── 7. RabbitMQ Broadcast ─────────────────────────────────────────────
        $eventPayload = [
            'message' => [
                'event'       => 'payroll.processed',
                'service'     => 'Payroll-Service',
                'team_id'     => env('TEAM_ID', 'TEAM-10'),
                'subject'     => $localUser['subject']    ?? null,
                'local_role'  => $localUser['local_role'] ?? null,
                'activity'    => 'PayrollRunCreated',
                'endpoint'    => 'POST /api/v1/payroll-runs',
                'nip'         => $payrollSlip->nip,
                'employee_name' => $emp['name'],
                'tahun'       => $payrollSlip->tahun,
                'bulan'       => $payrollSlip->bulan,
                'total_gaji'  => $payrollSlip->total_gaji,
                'status'      => $payrollSlip->status,
                'soap_receipt_number' => $auditResponse['receipt_number'],
                'integration' => [
                    'employee_service' => 'OK',
                    'absensi_service'  => 'OK',
                    'sso'              => 'OK',
                    'soap_audit'       => 'OK',
                ],
            ],
        ];

        $publishResponse = $publisherService->publish($token, $eventPayload);
        if (! $publishResponse['success']) {
            return $this->errorResponse(
                'Failed to publish payroll event to IAE RabbitMQ',
                ['status_code' => $publishResponse['status_code'], 'body' => $publishResponse['body']],
                $publishResponse['status_code']
            );
        }

        $payrollSlip->refresh();
        $responseData = $payrollSlip->toArray();
        $responseData['sources'] = [
            'employee' => [
                'employee_id'     => $emp['employee_id'],
                'name'            => $emp['name'],
                'department'      => $emp['department'],
                'position'        => $emp['position'],
                'base_salary'     => $gajiPokok,
                'fixed_allowance' => $tunjanganTetap,
                'status'          => $emp['status'],
            ],
            'attendance_summary' => $abs,
        ];
        $responseData['cloud_integration'] = [
            'sso_subject'         => $localUser['subject']         ?? null,
            'local_role'          => $localUser['local_role']       ?? null,
            'soap_status'         => $auditResponse['soap_status'],
            'soap_receipt_number' => $auditResponse['receipt_number'],
            'rabbitmq_status'     => $publishResponse['body']['status']   ?? null,
            'rabbitmq_exchange'   => $publishResponse['body']['exchange']  ?? null,
        ];

        return $this->successResponse(
            'Payroll processed successfully with SSO, SOAP Audit, and RabbitMQ',
            $responseData,
            201
        );
    }
}
