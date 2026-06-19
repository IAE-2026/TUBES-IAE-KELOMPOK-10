<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Absensi Service API",
 *     version="1.0.0",
 *     description="Service for managing employee attendance records. Part of the IAE Penggajian Karyawan ecosystem.",
 *     @OA\Contact(email="102022400074@telkomuniversity.ac.id")
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Absensi Service"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="ApiKeyAuth",
 *     type="apiKey",
 *     in="header",
 *     name="X-IAE-KEY",
 *     description="API Key for authentication. Use your NIM: 102022400074"
 * )
 *
 * @OA\Tag(name="Attendances", description="Employee Attendance management endpoints")
 */
class AttendanceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/attendances",
     *     summary="Get all attendance records",
     *     description="Returns all employee attendance records. Used by HR Admin for end-of-month audit before payroll.",
     *     operationId="getAttendances",
     *     tags={"Attendances"},
     *     security={{"ApiKeyAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Attendance")),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="service_name", type="string", example="Absensi-Service"),
     *                 @OA\Property(property="api_version", type="string", example="v1"),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized - Invalid or missing API Key")
     * )
     */
    public function index(): JsonResponse
    {
        $attendances = Attendance::orderBy('date', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $attendances,
            'meta' => [
                'service_name' => 'Absensi-Service',
                'api_version' => 'v1',
                'total' => $attendances->count(),
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/attendances/{start_date}/{end_date}",
     *     summary="Get attendance records by date range",
     *     description="Returns attendance records between start_date and end_date. HR Admin uses this to check employee presence, leave, sick, or absence in a given period.",
     *     operationId="getAttendancesByDateRange",
     *     tags={"Attendances"},
     *     security={{"ApiKeyAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="path",
     *         required=true,
     *         description="Start date in YYYY-MM-DD format",
     *         @OA\Schema(type="string", format="date", example="2025-05-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="path",
     *         required=true,
     *         description="End date in YYYY-MM-DD format",
     *         @OA\Schema(type="string", format="date", example="2025-05-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Attendance")),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="service_name", type="string", example="Absensi-Service"),
     *                 @OA\Property(property="api_version", type="string", example="v1"),
     *                 @OA\Property(property="period_start", type="string", example="2025-05-01"),
     *                 @OA\Property(property="period_end", type="string", example="2025-05-31"),
     *                 @OA\Property(property="total", type="integer", example=10)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Invalid date format")
     * )
     */
    public function showByDateRange(string $startDate, string $endDate): JsonResponse
    {
        $validator = Validator::make(
            ['start_date' => $startDate, 'end_date' => $endDate],
            [
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid date format. Use YYYY-MM-DD and ensure end_date >= start_date.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'data' => $attendances,
            'meta' => [
                'service_name' => 'Absensi-Service',
                'api_version' => 'v1',
                'period_start' => $startDate,
                'period_end' => $endDate,
                'total' => $attendances->count(),
            ],
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/attendances",
     *     summary="Record daily attendance",
     *     description="Records a daily attendance entry for an employee. Validates employee_id against the Employee Service before saving.",
     *     operationId="storeAttendance",
     *     tags={"Attendances"},
     *     security={{"ApiKeyAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"employee_id","date","status"},
     *             @OA\Property(property="employee_id", type="string", example="EMP-001"),
     *             @OA\Property(property="date", type="string", format="date", example="2025-05-15"),
     *             @OA\Property(property="status", type="string", enum={"hadir","izin","sakit","alpha"}, example="hadir"),
     *             @OA\Property(property="note", type="string", nullable=true, example="Izin keperluan keluarga")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Attendance recorded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Attendance recorded successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Attendance"),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="service_name", type="string", example="Absensi-Service"),
     *                 @OA\Property(property="api_version", type="string", example="v1")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Employee not found or inactive"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string|max:50',
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|in:hadir,izin,sakit,alpha',
            'note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate employee via Employee Service
        $employeeCheck = $this->validateEmployee($request->employee_id);
        if (!$employeeCheck['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => $employeeCheck['message'],
                'errors' => null,
            ], 404);
        }

        // Check for duplicate attendance on same day
        $existing = Attendance::where('employee_id', $request->employee_id)
            ->whereDate('date', $request->date)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Attendance for this employee on this date already exists.',
                'errors' => null,
            ], 422);
        }

        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'status' => $request->status,
            'note' => $request->note ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance recorded successfully',
            'data' => $attendance,
            'meta' => [
                'service_name' => 'Absensi-Service',
                'api_version' => 'v1',
            ],
        ], 201);
    }

    /**
     * Validate employee via Employee Service HTTP call.
     */
    private function validateEmployee(string $employeeId): array
    {
        $employeeServiceUrl = rtrim((string) config('services.employee_service_url'), '/');
        $employeeServiceKey = (string) config('services.employee_service_key');

        // If no employee service configured, allow (for local dev/testing)
        if (empty($employeeServiceUrl)) {
            return ['valid' => true, 'message' => 'Employee validation skipped (no service URL configured)'];
        }

        try {
            $response = Http::withHeaders([
                'X-IAE-KEY' => $employeeServiceKey,
                'Accept' => 'application/json',
            ])->timeout(5)->get("{$employeeServiceUrl}/api/v1/employees/" . urlencode($employeeId));

            if ($response->status() === 404) {
                return ['valid' => false, 'message' => "Employee with ID '{$employeeId}' not found."];
            }

            if (in_array($response->status(), [401, 403], true)) {
                return ['valid' => false, 'message' => 'Employee Service rejected the configured API key.'];
            }

            if (!$response->successful()) {
                // If employee service is down, log and allow (graceful degradation)
                \Log::warning("Employee service returned {$response->status()} for employee {$employeeId}");
                return ['valid' => true, 'message' => 'Employee service unavailable, proceeding without validation.'];
            }

            $body = $response->json();
            $employee = $body['data'] ?? null;

            if (!$employee) {
                return ['valid' => false, 'message' => "Employee with ID '{$employeeId}' not found."];
            }

            // Check if employee is active
            $status = $employee['status'] ?? $employee['status_karyawan'] ?? 'active';
            if (strtolower($status) !== 'active') {
                return ['valid' => false, 'message' => "Employee '{$employeeId}' is inactive and cannot be recorded for attendance."];
            }

            return ['valid' => true, 'message' => 'Employee is valid and active.'];
        } catch (\Exception $e) {
            \Log::warning("Employee service connection failed: " . $e->getMessage());
            // Graceful degradation: allow attendance if service is unreachable
            return ['valid' => true, 'message' => 'Employee service unreachable, proceeding without validation.'];
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/attendances/summary/{employeeId}/{year}/{month}",
     *     summary="Get monthly attendance summary per employee",
     *     description="Returns count of hadir/izin/sakit/alpha for a specific employee in a given month. Called internally by Payroll Service to calculate salary automatically.",
     *     operationId="getAttendanceMonthlySummary",
     *     tags={"Attendances"},
     *     security={{"ApiKeyAuth":{}}},
     *     @OA\Parameter(name="employeeId", in="path", required=true, description="Employee ID (e.g. EMP-001)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="year", in="path", required=true, description="Year (e.g. 2026)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="month", in="path", required=true, description="Month 1-12 (e.g. 5 for May)", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Summary retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Invalid year/month")
     * )
     *
     * Endpoint ini dipanggil Payroll Service (Farhan) saat menjalankan
     * POST /api/v1/payroll-runs untuk mengambil rekap absensi secara otomatis.
     */
    public function getMonthlySummary(string $employeeId, int $year, int $month): JsonResponse
    {
        // Validasi range bulan dan tahun
        $validator = Validator::make(
            ['year' => $year, 'month' => $month],
            [
                'year'  => 'required|integer|min:2020|max:2099',
                'month' => 'required|integer|min:1|max:12',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid year or month.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Hitung rentang tanggal bulan tersebut
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $lastDay   = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $endDate   = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $summary = [
            'employee_id'   => $employeeId,
            'year'          => $year,
            'month'         => $month,
            'period'        => "{$startDate} to {$endDate}",
            'jumlah_hadir'  => $attendances->where('status', 'hadir')->count(),
            'jumlah_izin'   => $attendances->where('status', 'izin')->count(),
            'jumlah_sakit'  => $attendances->where('status', 'sakit')->count(),
            'jumlah_alpha'  => $attendances->where('status', 'alpha')->count(),
            'total_records' => $attendances->count(),
        ];

        return response()->json([
            'status'  => 'success',
            'message' => 'Attendance summary retrieved successfully',
            'data'    => $summary,
            'meta'    => [
                'service_name' => 'Absensi-Service',
                'api_version'  => 'v1',
            ],
        ], 200);
    }
}
