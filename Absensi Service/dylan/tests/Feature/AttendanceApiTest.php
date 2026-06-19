<?php

namespace Tests\Feature;

use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = '102022400074';
    private string $wrongKey = 'wrong-key';

    // ─── API Key Protection Tests ────────────────────────────────────────────

    public function test_index_requires_api_key(): void
    {
        $response = $this->getJson('/api/v1/attendances');
        $response->assertStatus(401)
            ->assertJson(['status' => 'error']);
    }

    public function test_store_requires_api_key(): void
    {
        $response = $this->postJson('/api/v1/attendances', []);
        $response->assertStatus(401)
            ->assertJson(['status' => 'error']);
    }

    public function test_index_rejects_wrong_api_key(): void
    {
        $response = $this->getJson('/api/v1/attendances', ['X-IAE-KEY' => $this->wrongKey]);
        $response->assertStatus(401);
    }

    // ─── GET /api/v1/attendances ─────────────────────────────────────────────

    public function test_index_returns_all_attendances(): void
    {
        Attendance::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/attendances', ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [['id', 'employee_id', 'date', 'status']],
                'meta' => ['service_name', 'api_version', 'total'],
            ])
            ->assertJson([
                'status' => 'success',
                'meta' => ['service_name' => 'Absensi-Service', 'api_version' => 'v1'],
            ]);
    }

    public function test_index_returns_empty_when_no_attendances(): void
    {
        $response = $this->getJson('/api/v1/attendances', ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success', 'data' => []]);
    }

    // ─── GET /api/v1/attendances/{start}/{end} ───────────────────────────────

    public function test_show_by_date_range_returns_filtered_attendances(): void
    {
        Attendance::factory()->create(['date' => '2025-05-01', 'employee_id' => 'EMP-001']);
        Attendance::factory()->create(['date' => '2025-05-15', 'employee_id' => 'EMP-002']);
        Attendance::factory()->create(['date' => '2025-06-01', 'employee_id' => 'EMP-003']);

        $response = $this->getJson('/api/v1/attendances/2025-05-01/2025-05-31', ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonCount(2, 'data');
    }

    public function test_show_by_date_range_returns_422_for_invalid_dates(): void
    {
        $response = $this->getJson('/api/v1/attendances/not-a-date/2025-05-31', ['X-IAE-KEY' => $this->apiKey]);
        $response->assertStatus(422)->assertJson(['status' => 'error']);
    }

    public function test_show_by_date_range_returns_422_when_end_before_start(): void
    {
        $response = $this->getJson('/api/v1/attendances/2025-05-31/2025-05-01', ['X-IAE-KEY' => $this->apiKey]);
        $response->assertStatus(422)->assertJson(['status' => 'error']);
    }

    public function test_show_by_date_range_meta_contains_period_info(): void
    {
        $response = $this->getJson('/api/v1/attendances/2025-05-01/2025-05-31', ['X-IAE-KEY' => $this->apiKey]);
        $response->assertStatus(200)
            ->assertJsonPath('meta.period_start', '2025-05-01')
            ->assertJsonPath('meta.period_end', '2025-05-31');
    }

    // ─── POST /api/v1/attendances ────────────────────────────────────────────

    public function test_store_creates_attendance_without_employee_service(): void
    {
        // When EMPLOYEE_SERVICE_URL is empty, validation is skipped (graceful degradation)
        config(['services.employee_service_url' => '']);

        $response = $this->postJson('/api/v1/attendances', [
            'employee_id' => 'EMP-001',
            'date' => '2025-05-15',
            'status' => 'hadir',
        ], ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Attendance recorded successfully',
            ])
            ->assertJsonPath('data.employee_id', 'EMP-001')
            ->assertJsonPath('data.status', 'hadir');

        $this->assertTrue(
            Attendance::where('employee_id', 'EMP-001')
                ->whereDate('date', '2025-05-15')
                ->where('status', 'hadir')
                ->exists()
        );
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/attendances', [], ['X-IAE-KEY' => $this->apiKey]);
        $response->assertStatus(422)->assertJson(['status' => 'error']);
    }

    public function test_store_validates_status_enum(): void
    {
        config(['services.employee_service_url' => '']);

        $response = $this->postJson('/api/v1/attendances', [
            'employee_id' => 'EMP-001',
            'date' => '2025-05-15',
            'status' => 'tidak-valid',
        ], ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(422)->assertJson(['status' => 'error']);
    }

    public function test_store_prevents_duplicate_attendance(): void
    {
        config(['services.employee_service_url' => '']);

        Attendance::factory()->create(['employee_id' => 'EMP-001', 'date' => '2025-05-15']);

        $response = $this->postJson('/api/v1/attendances', [
            'employee_id' => 'EMP-001',
            'date' => '2025-05-15',
            'status' => 'hadir',
        ], ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(422)->assertJson(['status' => 'error']);
    }

    public function test_store_rejects_inactive_employee_from_employee_service(): void
    {
        Http::fake([
            '*/api/v1/employees/EMP-INACTIVE' => Http::response([
                'status' => 'success',
                'data' => ['employee_id' => 'EMP-INACTIVE', 'status' => 'inactive'],
            ], 200),
        ]);
        config(['services.employee_service_url' => 'http://employee-service:8000']);

        $response = $this->postJson('/api/v1/attendances', [
            'employee_id' => 'EMP-INACTIVE',
            'date' => '2025-05-15',
            'status' => 'hadir',
        ], ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(404)->assertJson(['status' => 'error']);
    }

    public function test_store_rejects_nonexistent_employee_from_employee_service(): void
    {
        Http::fake([
            '*/api/v1/employees/EMP-GHOST' => Http::response([
                'status' => 'error',
                'message' => 'Not found',
            ], 404),
        ]);
        config(['services.employee_service_url' => 'http://employee-service:8000']);

        $response = $this->postJson('/api/v1/attendances', [
            'employee_id' => 'EMP-GHOST',
            'date' => '2025-05-15',
            'status' => 'hadir',
        ], ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(404)->assertJson(['status' => 'error']);
    }

    public function test_store_rejects_invalid_employee_service_api_key(): void
    {
        Http::fake([
            '*/api/v1/employees/EMP-001' => Http::response([
                'status' => 'error',
                'message' => 'API key tidak valid atau tidak dikirim.',
                'errors' => null,
            ], 401),
        ]);
        config(['services.employee_service_url' => 'http://employee-service:8000']);
        config(['services.employee_service_key' => 'wrong-key']);

        $response = $this->postJson('/api/v1/attendances', [
            'employee_id' => 'EMP-001',
            'date' => '2025-05-15',
            'status' => 'hadir',
        ], ['X-IAE-KEY' => $this->apiKey]);

        $response->assertStatus(404)
            ->assertJson(['status' => 'error'])
            ->assertJsonPath('message', 'Employee Service rejected the configured API key.');
    }

    // ─── Response Format Tests ───────────────────────────────────────────────

    public function test_success_response_follows_standard_integration_contract(): void
    {
        $response = $this->getJson('/api/v1/attendances', ['X-IAE-KEY' => $this->apiKey]);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data', 'meta']);
        $this->assertEquals('success', $response->json('status'));
    }

    public function test_error_response_follows_standard_integration_contract(): void
    {
        $response = $this->getJson('/api/v1/attendances');
        $response->assertStatus(401)
            ->assertJsonStructure(['status', 'message', 'errors']);
        $this->assertEquals('error', $response->json('status'));
    }

    // ─── Swagger / GraphQL Availability Tests ────────────────────────────────

    public function test_swagger_documentation_is_accessible(): void
    {
        $response = $this->get('/api/documentation');
        $response->assertStatus(200);
    }

    public function test_graphql_endpoint_is_accessible(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => '{ attendances { id employee_id date status } }',
        ]);
        // GraphQL always returns 200 even for empty results
        $response->assertStatus(200);
    }
}
