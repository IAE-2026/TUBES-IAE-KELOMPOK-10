<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\CentralAuditClient;
use App\Services\CentralMessagePublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AttendanceTugas3Controller extends Controller
{
    public function store(
        Request $request,
        CentralAuditClient $auditClient,
        CentralMessagePublisher $messagePublisher
    ): JsonResponse {
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

        $employeeCheck = $this->validateEmployee((string) $request->employee_id);
        if (!$employeeCheck['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => $employeeCheck['message'],
                'errors' => null,
            ], 404);
        }

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

        $actor = $request->attributes->get('central_user', []);
        $role = (string) $request->attributes->get('central_role', 'guest');
        $eventId = (string) Str::uuid();

        $auditPayload = [
            'event_id' => $eventId,
            'service' => config('iae.service_name'),
            'transaction' => 'record_daily_attendance',
            'employee_id' => $request->employee_id,
            'attendance_date' => $request->date,
            'attendance_status' => $request->status,
            'note' => $request->note,
            'actor' => $actor,
            'actor_role' => $role,
            'created_at' => now()->toIso8601String(),
        ];

        try {
            $audit = $auditClient->send('AttendanceRecorded', $auditPayload);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Absensi belum disimpan karena SOAP audit ke sistem dosen gagal.',
                'errors' => $e->getMessage(),
            ], 502);
        }

        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'status' => $request->status,
            'note' => $request->note ?? null,
            'created_by_email' => $actor['email'] ?? null,
            'created_by_name' => $actor['name'] ?? null,
            'local_role' => $role,
            'audit_status' => $audit['status'],
            'audit_receipt_number' => $audit['receipt_number'],
            'central_event_id' => $eventId,
        ]);

        $routingKey = (string) config('iae.attendance_recorded_routing_key');
$eventPayload = [
    'service' => config('iae.service_name'), // Akan jadi "Absensi-Service"
    'event' => 'attendance.recorded',
    'occurred_at' => now()->toIso8601String(),
    'actor' => [
        'subject' => $actor['email'] ?? null,
        'email' => $actor['email'] ?? null,
        'local_role' => $role,
    ],
    'attendance' => [
        'attendance_id' => $attendance->id,
        'employee_id' => $attendance->employee_id,
        'date' => $attendance->date->format('Y-m-d'),
        'status' => $attendance->status,
        'note' => $attendance->note,
    ],
    'audit_receipt_number' => $audit['receipt_number'],
];

        try {
            $publish = $messagePublisher->publish($routingKey, $eventPayload);
            $attendance->update([
                'event_routing_key' => $routingKey,
                'event_published_at' => now(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Absensi sudah tersimpan dan sudah diaudit, tetapi publish RabbitMQ gagal.',
                'data' => $attendance,
                'errors' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance recorded, SOAP audit sent, and RabbitMQ event published.',
            'data' => $attendance->fresh(),
            'central' => [
                'actor' => $actor,
                'role' => $role,
                'soap_receipt_number' => $audit['receipt_number'],
                'rabbitmq_exchange' => $publish['exchange'] ?? config('iae.rabbit_exchange'),
                'rabbitmq_routing_key' => $publish['routing_key'] ?? $routingKey,
            ],
        ], 201);
    }

    private function validateEmployee(string $employeeId): array
    {
        $employeeServiceUrl = rtrim((string) config('services.employee_service_url'), '/');
        $employeeServiceKey = (string) config('services.employee_service_key');

        if ($employeeServiceUrl === '') {
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
                return ['valid' => true, 'message' => 'Employee service unavailable, proceeding without validation.'];
            }

            $employee = $response->json('data');
            if (!$employee) {
                return ['valid' => false, 'message' => "Employee with ID '{$employeeId}' not found."];
            }

            $status = $employee['status'] ?? $employee['status_karyawan'] ?? 'active';
            if (strtolower((string) $status) !== 'active') {
                return ['valid' => false, 'message' => "Employee '{$employeeId}' is inactive and cannot be recorded for attendance."];
            }

            return ['valid' => true, 'message' => 'Employee is valid and active.'];
        } catch (Throwable $e) {
            return ['valid' => true, 'message' => 'Employee service unreachable, proceeding without validation.'];
        }
    }
}
