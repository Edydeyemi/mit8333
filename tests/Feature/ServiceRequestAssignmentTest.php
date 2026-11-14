<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Role;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $officerA;
    protected User $officerB;
    protected User $student;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'email' => 'admin_assign_test@headworld.edu',
        ]);
        $adminRole = Role::where('role_slug', 'admin')->first();
        if ($adminRole) {
            $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $officerRole = Role::where('role_slug', 'officer')->first();

        $this->officerA = User::factory()->create([
            'role' => 'officer',
            'status' => 'active',
            'email' => 'officera_test@headworld.edu',
        ]);
        if ($officerRole) {
            $this->officerA->roles()->syncWithoutDetaching([$officerRole->id]);
        }

        $this->officerB = User::factory()->create([
            'role' => 'officer',
            'status' => 'active',
            'email' => 'officerb_test@headworld.edu',
        ]);
        if ($officerRole) {
            $this->officerB->roles()->syncWithoutDetaching([$officerRole->id]);
        }

        $this->student = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'student_assign_test@headworld.edu',
        ]);
        $userRole = Role::where('role_slug', 'user')->first();
        if ($userRole) {
            $this->student->roles()->syncWithoutDetaching([$userRole->id]);
        }

        $this->category = ServiceCategory::first();
    }

    public function test_admin_can_assign_technician_and_creates_service_request_assignment(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'title' => 'Blown fuse in laboratory power socket',
            'description' => 'Power socket sparking and non-functional near station 4.',
            'priority' => 'high',
            'status' => 'submitted',
            'building' => 'Engineering Complex Block A',
            'wing_floor' => 'Floor 2',
            'room_number' => 'Lab 204',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.requests.assign', $serviceRequest->slug), [
            'assigned_officer_id' => $this->officerA->id,
            'notes' => 'Please bring replacement fuses and multi-meter.',
        ]);

        $response->assertRedirect();
        $serviceRequest->refresh();

        $this->assertEquals('assigned', $serviceRequest->status);
        $this->assertEquals($this->officerA->id, $serviceRequest->assigned_officer_id);

        // Verify record in service_request_assignments table
        $this->assertDatabaseHas('service_request_assignments', [
            'service_request_id' => $serviceRequest->id,
            'officer_id' => $this->officerA->id,
            'assigned_by_id' => $this->admin->id,
            'status' => 'active',
            'notes' => 'Please bring replacement fuses and multi-meter.',
        ]);

        $this->assertNotNull($serviceRequest->currentAssignment);
        $this->assertEquals($this->officerA->id, $serviceRequest->currentAssignment->officer_id);
    }

    public function test_reassigning_officer_marks_previous_assignment_as_reassigned_and_creates_new(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'title' => 'AC unit leak in lecture hall',
            'description' => 'Water dripping on overhead projector.',
            'priority' => 'medium',
            'status' => 'submitted',
            'building' => 'Maxwell Lecture Theatres',
            'wing_floor' => 'Floor 1',
            'room_number' => 'LT 1',
        ]);

        // First assignment to Officer A
        $this->actingAs($this->admin)->post(route('admin.requests.assign', $serviceRequest->slug), [
            'assigned_officer_id' => $this->officerA->id,
            'notes' => 'First assignment',
        ]);

        $firstAssignment = ServiceRequestAssignment::where('service_request_id', $serviceRequest->id)
            ->where('officer_id', $this->officerA->id)
            ->first();

        $this->assertNotNull($firstAssignment);
        $this->assertEquals('active', $firstAssignment->status);

        // Reassign to Officer B
        $this->actingAs($this->admin)->post(route('admin.requests.assign', $serviceRequest->slug), [
            'assigned_officer_id' => $this->officerB->id,
            'notes' => 'Officer A reassigned to higher priority emergency.',
        ]);

        $firstAssignment->refresh();
        $this->assertEquals('reassigned', $firstAssignment->status);
        $this->assertNotNull($firstAssignment->unassigned_at);

        $secondAssignment = ServiceRequestAssignment::where('service_request_id', $serviceRequest->id)
            ->where('officer_id', $this->officerB->id)
            ->first();

        $this->assertNotNull($secondAssignment);
        $this->assertEquals('active', $secondAssignment->status);
        $this->assertEquals(2, $serviceRequest->assignments()->count());
    }

    public function test_resolving_task_marks_active_assignment_as_completed(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'title' => 'Faulty classroom projector',
            'description' => 'Lamp failed and needs bulb replacement.',
            'priority' => 'medium',
            'status' => 'submitted',
            'building' => 'Maxwell Lecture Theatres',
            'wing_floor' => 'Floor 1',
            'room_number' => 'LT 2',
        ]);

        // Assign to Officer A
        $this->actingAs($this->admin)->post(route('admin.requests.assign', $serviceRequest->slug), [
            'assigned_officer_id' => $this->officerA->id,
        ]);

        // Officer resolves the task
        $response = $this->actingAs($this->officerA)->post(route('facility.tasks.status', $serviceRequest->slug), [
            'status' => 'resolved',
            'notes' => 'Bulb replaced and tested with projector test pattern.',
        ]);

        $response->assertRedirect();
        $serviceRequest->refresh();

        $this->assertEquals('resolved', $serviceRequest->status);

        $assignment = ServiceRequestAssignment::where('service_request_id', $serviceRequest->id)->first();
        $this->assertEquals('completed', $assignment->status);
    }

    public function test_cancelling_ticket_marks_active_assignment_as_cancelled(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'title' => 'Water valve drip',
            'description' => 'Minor drip in washroom.',
            'priority' => 'low',
            'status' => 'submitted',
            'building' => "Queen's Hall (Hostel)",
            'wing_floor' => 'Floor 2',
            'room_number' => 'Room 205',
        ]);

        // Assign to Officer A
        $this->actingAs($this->admin)->post(route('admin.requests.assign', $serviceRequest->slug), [
            'assigned_officer_id' => $this->officerA->id,
        ]);

        // Student cancels the request
        $this->actingAs($this->student)->post(route('user.requests.cancel', $serviceRequest->slug), [
            'cancellation_reason' => 'Plumber fixed it directly.',
        ]);

        $serviceRequest->refresh();
        $this->assertEquals('cancelled', $serviceRequest->status);

        $assignment = ServiceRequestAssignment::where('service_request_id', $serviceRequest->id)->first();
        $this->assertEquals('cancelled', $assignment->status);
        $this->assertNotNull($assignment->unassigned_at);
    }
}
