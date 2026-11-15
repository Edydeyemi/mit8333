<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Admin\Role;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceRequestApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $officer;
    protected User $student;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->superAdmin = User::where('role', 'super_admin')->first();

        // Create Officer
        $this->officer = User::factory()->create([
            'role' => 'officer',
            'status' => 'active',
            'first_name' => 'Marcus',
            'last_name' => 'Bennett',
            'email' => 'officer_api@headworld.edu',
        ]);
        $officerRole = Role::where('role_slug', 'officer')->first();
        if ($officerRole) {
            $this->officer->roles()->syncWithoutDetaching([$officerRole->id]);
        }

        // Create Student User
        $this->student = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'student_api@headworld.edu',
        ]);
        $userRole = Role::where('role_slug', 'user')->first();
        if ($userRole) {
            $this->student->roles()->syncWithoutDetaching([$userRole->id]);
        }

        $this->category = ServiceCategory::firstOrCreate(
            ['slug' => 'electrical-lighting'],
            [
                'name' => 'Electrical & Lighting',
                'description' => 'Power sockets, breakers, lighting',
                'icon' => 'bi-lightning-charge',
                'sla_hours' => 24,
                'is_active' => true,
            ]
        );
    }

    public function test_public_can_fetch_active_service_categories(): void
    {
        $response = $this->getJson(route('api.v1.categories.index'));

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'description', 'icon', 'sla_hours'],
                ],
            ]);
    }

    public function test_unauthenticated_request_to_protected_api_is_rejected(): void
    {
        $response = $this->getJson(route('api.v1.requests.index'));

        $response->assertStatus(401);
    }

    public function test_student_can_lodge_service_request_via_sanctum_api(): void
    {
        Sanctum::actingAs($this->student);
        Storage::fake('public');

        $file = UploadedFile::fake()->image('fault.jpg', 600, 600);

        $payload = [
            'service_category_id' => $this->category->id,
            'priority' => 'emergency',
            'building' => "Queen's Hall",
            'wing_floor' => 'Level 2',
            'room_number' => 'Room 204',
            'title' => 'Buzzing electrical box sparking',
            'description' => 'Heavy sparks emitting near the door frame of room 204.',
            'attachments' => [$file],
        ];

        $response = $this->postJson(route('api.v1.requests.store'), $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Service request lodged successfully.',
            ]);

        $this->assertDatabaseHas('service_requests', [
            'user_id' => $this->student->id,
            'title' => 'Buzzing electrical box sparking',
            'priority' => 'emergency',
            'status' => 'submitted',
        ]);
    }

    public function test_admin_can_assign_officer_via_api(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'priority' => 'high',
            'building' => 'Engineering Complex',
            'wing_floor' => 'Floor 1',
            'room_number' => 'Lab A',
            'title' => 'Broken AC thermostat',
            'description' => 'Thermostat is stuck at 30 degrees and not cooling.',
            'status' => 'submitted',
        ]);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson(route('api.v1.requests.assign', $serviceRequest->slug), [
            'assigned_officer_id' => $this->officer->id,
            'notes' => 'Assigned for immediate inspection.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Technician assigned successfully.',
            ]);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'assigned_officer_id' => $this->officer->id,
            'status' => 'assigned',
        ]);
    }

    public function test_assigned_officer_can_update_status_to_resolved_via_api(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'assigned_officer_id' => $this->officer->id,
            'priority' => 'high',
            'building' => 'Engineering Complex',
            'wing_floor' => 'Floor 1',
            'room_number' => 'Lab A',
            'title' => 'Broken AC thermostat',
            'description' => 'Thermostat is stuck at 30 degrees and not cooling.',
            'status' => 'in_progress',
        ]);

        Sanctum::actingAs($this->officer);

        $response = $this->patchJson(route('api.v1.requests.status', $serviceRequest->slug), [
            'status' => 'resolved',
            'notes' => 'Thermostat recalibrated and temperature sensor replaced.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Work order status updated successfully.',
            ]);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'resolved',
        ]);
    }

    public function test_student_can_cancel_own_unassigned_request_via_api(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'priority' => 'low',
            'building' => 'Hostel Block B',
            'wing_floor' => 'Ground Floor',
            'room_number' => 'Room 12',
            'title' => 'Loose cupboard handle',
            'description' => 'Handle came off cabinet door.',
            'status' => 'submitted',
        ]);

        Sanctum::actingAs($this->student);

        $response = $this->postJson(route('api.v1.requests.cancel', $serviceRequest->slug), [
            'reason' => 'Roommate tightened the screw, issue resolved.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Service request cancelled successfully.',
            ]);

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'status' => 'cancelled',
        ]);
    }
}

