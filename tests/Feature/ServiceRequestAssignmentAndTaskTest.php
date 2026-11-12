<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAttachment;
use App\Models\ServiceRequestStatusUpdate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceRequestAssignmentAndTaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $officerA;
    protected User $officerB;
    protected User $student;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->superAdmin = User::where('role', 'super_admin')->first();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'first_name' => 'Dean',
            'last_name' => 'Sterling',
            'email' => 'admin_test@headworld.edu',
        ]);
        // Attach admin role permissions
        $adminRole = \App\Models\Admin\Role::where('role_slug', 'admin')->first();
        if ($adminRole) {
            $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $officerRole = \App\Models\Admin\Role::where('role_slug', 'officer')->first();

        $this->officerA = User::factory()->create([
            'role' => 'officer',
            'status' => 'active',
            'first_name' => 'Marcus',
            'last_name' => 'Bennett',
            'email' => 'marcus@headworld.edu',
        ]);
        if ($officerRole) {
            $this->officerA->roles()->syncWithoutDetaching([$officerRole->id]);
        }

        $this->officerB = User::factory()->create([
            'role' => 'officer',
            'status' => 'active',
            'first_name' => 'David',
            'last_name' => 'Kim',
            'email' => 'david@headworld.edu',
        ]);
        if ($officerRole) {
            $this->officerB->roles()->syncWithoutDetaching([$officerRole->id]);
        }

        $this->student = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'student_tester@headworld.edu',
        ]);
        $userRole = \App\Models\Admin\Role::where('role_slug', 'user')->first();
        if ($userRole) {
            $this->student->roles()->syncWithoutDetaching([$userRole->id]);
        }

        $this->category = ServiceCategory::firstOrCreate(
            ['slug' => 'electrical-fixtures'],
            [
                'name' => 'Faulty Electricity & Lighting',
                'description' => 'Light fixture faults and circuit issues',
                'icon' => 'bi-lightning-charge',
                'color' => '#d4af37',
                'is_active' => true,
            ]
        );
    }

    protected function createServiceRequest(array $attributes = []): ServiceRequest
    {
        return ServiceRequest::create(array_merge([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'title' => 'Flickering Fluorescent in Lab 204',
            'description' => 'Fluorescent bulb ballast is buzzing and flashing constantly.',
            'priority' => 'high',
            'status' => 'submitted',
            'building' => "Engineering Complex Block A",
            'wing_floor' => 'Floor 2 West',
            'room_number' => 'Lab 204',
        ], $attributes));
    }

    public function test_admin_can_view_dispatch_requests_index(): void
    {
        $this->createServiceRequest();

        $response = $this->actingAs($this->admin)->get(route('admin.requests.index'));

        $response->assertStatus(200);
        $response->assertSee('Master Incident Dispatch Console');
        $response->assertSee('Flickering Fluorescent in Lab 204');
        $response->assertSee('badge-priority priority-high', false);
        $response->assertSee('badge-status badge-submitted', false);
        $response->assertSee('status-dot', false);
    }

    public function test_admin_can_assign_request_to_facility_officer(): void
    {
        $request = $this->createServiceRequest();

        $response = $this->actingAs($this->admin)->post(
            route('admin.requests.assign', $request->slug),
            [
                'assigned_officer_id' => $this->officerA->id,
                'notes' => 'Priority dispatch: please replace ballast before 4pm.',
            ]
        );

        $response->assertRedirect();

        $request->refresh();
        $this->assertSame($this->officerA->id, $request->assigned_officer_id);
        $this->assertSame('assigned', $request->status);
        $this->assertNotNull($request->assigned_at);

        // Verify status updates table record
        $this->assertDatabaseHas('service_request_status_updates', [
            'service_request_id' => $request->id,
            'user_id' => $this->admin->id,
            'previous_status' => 'submitted',
            'status' => 'assigned',
        ]);

        $statusUpdate = ServiceRequestStatusUpdate::where('service_request_id', $request->id)->first();
        $this->assertStringContainsString('Marcus Bennett', $statusUpdate->notes);
        $this->assertStringContainsString('replace ballast before 4pm', $statusUpdate->notes);

        // Verify audit log record
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ServiceRequest::class,
            'auditable_id' => $request->id,
            'action' => 'assigned',
            'actor_id' => $this->admin->id,
        ]);
    }

    public function test_non_admin_cannot_assign_request(): void
    {
        $request = $this->createServiceRequest();

        $response = $this->actingAs($this->student)->post(
            route('admin.requests.assign', $request->slug),
            [
                'assigned_officer_id' => $this->officerA->id,
            ]
        );

        $response->assertStatus(403);
    }

    public function test_facility_officer_sees_assigned_tasks_in_kanban_and_desk(): void
    {
        $assignedTask = $this->createServiceRequest([
            'assigned_officer_id' => $this->officerA->id,
            'assigned_at' => now(),
            'status' => 'assigned',
            'title' => 'Task for Marcus Bennett',
        ]);

        $otherOfficerTask = $this->createServiceRequest([
            'assigned_officer_id' => $this->officerB->id,
            'assigned_at' => now(),
            'status' => 'assigned',
            'title' => 'Task for David Kim',
        ]);

        // Officer A visits task pipeline
        $response = $this->actingAs($this->officerA)->get(route('facility.tasks.index'));
        $response->assertStatus(200);
        $response->assertSee('Task Management Pipeline');
        $response->assertSee('Task for Marcus Bennett');
        $response->assertDontSee('Task for David Kim');

        // Officer A visits dashboard desk
        $deskResponse = $this->actingAs($this->officerA)->get(route('facility.dashboard'));
        $deskResponse->assertStatus(200);
        $deskResponse->assertSee('Campus Facilities Field Desk');
        $deskResponse->assertSee('Task for Marcus Bennett');
    }

    public function test_facility_officer_can_update_status_to_in_progress(): void
    {
        $task = $this->createServiceRequest([
            'assigned_officer_id' => $this->officerA->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($this->officerA)->post(
            route('facility.tasks.status', $task->slug),
            [
                'status' => 'in_progress',
                'notes' => 'Arrived at Lab 204. Isolated breaker 4B.',
            ]
        );

        $response->assertRedirect();

        $task->refresh();
        $this->assertSame('in_progress', $task->status);

        // Verify status update in separate table
        $this->assertDatabaseHas('service_request_status_updates', [
            'service_request_id' => $task->id,
            'user_id' => $this->officerA->id,
            'previous_status' => 'assigned',
            'status' => 'in_progress',
            'notes' => 'Arrived at Lab 204. Isolated breaker 4B.',
        ]);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ServiceRequest::class,
            'auditable_id' => $task->id,
            'action' => 'status_updated',
            'actor_id' => $this->officerA->id,
        ]);
    }

    public function test_facility_officer_can_update_status_to_on_hold(): void
    {
        $task = $this->createServiceRequest([
            'assigned_officer_id' => $this->officerA->id,
            'assigned_at' => now(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->officerA)->post(
            route('facility.tasks.status', $task->slug),
            [
                'status' => 'on_hold',
                'notes' => 'Ballast model T8-58W is out of stock at central depot. Waiting for supply truck.',
            ]
        );

        $response->assertRedirect();

        $task->refresh();
        $this->assertSame('on_hold', $task->status);

        $this->assertDatabaseHas('service_request_status_updates', [
            'service_request_id' => $task->id,
            'user_id' => $this->officerA->id,
            'previous_status' => 'in_progress',
            'status' => 'on_hold',
        ]);
    }

    public function test_facility_officer_can_resolve_task_with_notes_and_attachments(): void
    {
        Storage::fake('public');

        $task = $this->createServiceRequest([
            'assigned_officer_id' => $this->officerA->id,
            'assigned_at' => now(),
            'status' => 'in_progress',
        ]);

        $photoFile = UploadedFile::fake()->image('repaired_ballast.jpg', 600, 400);

        $response = $this->actingAs($this->officerA)->post(
            route('facility.tasks.status', $task->slug),
            [
                'status' => 'resolved',
                'notes' => 'Replaced faulty electronic ballast with new Philips 58W unit. Voltage tested at 230V.',
                'attachments' => [$photoFile],
            ]
        );

        $response->assertRedirect();

        $task->refresh();
        $this->assertSame('resolved', $task->status);
        $this->assertNotNull($task->resolved_at);

        // Verify status update record
        $statusUpdate = ServiceRequestStatusUpdate::where('service_request_id', $task->id)
            ->where('status', 'resolved')
            ->first();

        $this->assertNotNull($statusUpdate);
        $this->assertSame('Replaced faulty electronic ballast with new Philips 58W unit. Voltage tested at 230V.', $statusUpdate->notes);

        // Verify attachment is linked to both service request and status update
        $this->assertDatabaseHas('service_request_attachments', [
            'service_request_id' => $task->id,
            'status_update_id' => $statusUpdate->id,
            'file_name' => 'repaired_ballast.jpg',
            'type' => 'resolution_proof',
            'uploaded_by' => $this->officerA->id,
        ]);

        // Verify attachment relationship on statusUpdate model
        $this->assertCount(1, $statusUpdate->attachments);

        // Verify audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ServiceRequest::class,
            'auditable_id' => $task->id,
            'action' => 'status_updated',
            'actor_id' => $this->officerA->id,
        ]);
    }

    public function test_officer_cannot_update_task_assigned_to_another_officer(): void
    {
        $task = $this->createServiceRequest([
            'assigned_officer_id' => $this->officerA->id,
            'assigned_at' => now(),
            'status' => 'assigned',
        ]);

        // Officer B tries to update Officer A's task
        $response = $this->actingAs($this->officerB)->post(
            route('facility.tasks.status', $task->slug),
            [
                'status' => 'in_progress',
                'notes' => 'Illegitimate update attempt',
            ]
        );

        $response->assertStatus(403);

        $task->refresh();
        $this->assertSame('assigned', $task->status);
    }
}

