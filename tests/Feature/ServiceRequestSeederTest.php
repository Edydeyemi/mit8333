<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAssignment;
use App\Models\ServiceRequestAttachment;
use App\Models\ServiceRequestStatusUpdate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ServiceRequestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_service_requests_are_seeded_with_expected_volume_and_stages(): void
    {
        $this->assertGreaterThanOrEqual(14, ServiceRequest::count());

        // Verify all 6 stages exist
        $stages = ['submitted', 'assigned', 'in_progress', 'on_hold', 'resolved', 'cancelled'];
        foreach ($stages as $stage) {
            $this->assertTrue(
                ServiceRequest::where('status', $stage)->exists(),
                "Expected service requests with status '{$stage}' to exist."
            );
        }

        // Verify priorities distribution
        $priorities = ['emergency', 'high', 'medium', 'low'];
        foreach ($priorities as $priority) {
            $this->assertTrue(
                ServiceRequest::where('priority', $priority)->exists(),
                "Expected service requests with priority '{$priority}' to exist."
            );
        }
    }

    public function test_service_requests_are_mapped_to_users(): void
    {
        $student = User::where('email', 'student@unilag.edu')->firstOrFail();
        $staff = User::where('email', 'staff@unilag.edu')->firstOrFail();
        $demoUser = User::where('email', 'user@in.com')->firstOrFail();

        $this->assertGreaterThanOrEqual(1, ServiceRequest::where('user_id', $student->id)->count());
        $this->assertGreaterThanOrEqual(1, ServiceRequest::where('user_id', $staff->id)->count());
        $this->assertGreaterThanOrEqual(1, ServiceRequest::where('user_id', $demoUser->id)->count());
    }

    public function test_service_requests_are_assigned_to_different_officers(): void
    {
        $officer1 = User::where('email', 'officer@unilag.edu')->firstOrFail();
        $officer2 = User::where('email', 'officer2@unilag.edu')->firstOrFail();
        $officer3 = User::where('email', 'officer3@unilag.edu')->firstOrFail();

        $this->assertGreaterThanOrEqual(1, ServiceRequest::where('assigned_officer_id', $officer1->id)->count());
        $this->assertGreaterThanOrEqual(1, ServiceRequest::where('assigned_officer_id', $officer2->id)->count());
        $this->assertGreaterThanOrEqual(1, ServiceRequest::where('assigned_officer_id', $officer3->id)->count());

        // Unassigned requests must also exist
        $this->assertGreaterThanOrEqual(1, ServiceRequest::whereNull('assigned_officer_id')->count());
    }

    public function test_service_request_status_updates_and_attachments_are_populated(): void
    {
        $this->assertGreaterThanOrEqual(20, ServiceRequestStatusUpdate::count());
        $this->assertGreaterThanOrEqual(14, ServiceRequestAttachment::count());
        $this->assertGreaterThanOrEqual(10, ServiceRequestAssignment::count());

        // Evidence and resolution proof attachments exist
        $this->assertTrue(ServiceRequestAttachment::where('type', 'evidence')->exists());
        $this->assertTrue(ServiceRequestAttachment::where('type', 'resolution_proof')->exists());

        // Assignments exist with active, completed, and reassigned statuses
        $this->assertTrue(ServiceRequestAssignment::where('status', 'active')->exists());
        $this->assertTrue(ServiceRequestAssignment::where('status', 'completed')->exists());
        $this->assertTrue(ServiceRequestAssignment::where('status', 'reassigned')->exists());

        // Resolved requests have resolved_at timestamps
        $resolvedRequests = ServiceRequest::where('status', 'resolved')->get();
        $this->assertNotEmpty($resolvedRequests);
        foreach ($resolvedRequests as $req) {
            $this->assertNotNull($req->resolved_at);
        }

        // Cancelled requests have cancellation_reason and cancelled_at
        $cancelledRequests = ServiceRequest::where('status', 'cancelled')->get();
        $this->assertNotEmpty($cancelledRequests);
        foreach ($cancelledRequests as $req) {
            $this->assertNotNull($req->cancelled_at);
            $this->assertNotEmpty($req->cancellation_reason);
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $initialRequestCount = ServiceRequest::count();
        $initialUpdateCount = ServiceRequestStatusUpdate::count();
        $initialAttachmentCount = ServiceRequestAttachment::count();
        $initialAssignmentCount = ServiceRequestAssignment::count();

        // Run seeder second time
        $this->seed(ServiceRequestSeeder::class);

        $this->assertSame($initialRequestCount, ServiceRequest::count());
        $this->assertSame($initialUpdateCount, ServiceRequestStatusUpdate::count());
        $this->assertSame($initialAttachmentCount, ServiceRequestAttachment::count());
        $this->assertSame($initialAssignmentCount, ServiceRequestAssignment::count());
    }
}

