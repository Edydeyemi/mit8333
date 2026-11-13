<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->superAdmin = User::where('role', 'super_admin')->first();
        $this->student = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'student_report_test@headworld.edu',
        ]);
    }

    public function test_admin_can_access_reports_and_audit_page(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Operational Compliance & Audit Intelligence', false);
        $response->assertSee('Campus Facility Performance Digest');
        $response->assertSee('Immutable Audit Stream');
    }

    public function test_unauthorized_user_cannot_access_reports(): void
    {
        $this->actingAs($this->student);

        $response = $this->get(route('admin.reports.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_export_service_requests_to_xlsx(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.reports.export.requests'));

        $response->assertStatus(200);
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('headworld_service_requests_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_admin_can_export_officer_kpis_to_xlsx(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.reports.export.officers'));

        $response->assertStatus(200);
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('headworld_officer_kpis_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }

    public function test_admin_can_export_audit_logs_to_xlsx(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.reports.export.audits'));

        $response->assertStatus(200);
        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString('headworld_audit_trail_', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
    }
}
