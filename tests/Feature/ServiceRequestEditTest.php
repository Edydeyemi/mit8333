<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Role;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceRequestEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $officer;
    protected ServiceCategory $categoryA;
    protected ServiceCategory $categoryB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $userRole = Role::where('role_slug', 'user')->first();

        $this->student = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'student_edit_test@headworld.edu',
        ]);
        if ($userRole) {
            $this->student->roles()->syncWithoutDetaching([$userRole->id]);
        }

        $this->otherStudent = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'email' => 'other_student_test@headworld.edu',
        ]);
        if ($userRole) {
            $this->otherStudent->roles()->syncWithoutDetaching([$userRole->id]);
        }

        $this->categoryA = ServiceCategory::first();
        $this->categoryB = ServiceCategory::skip(1)->first();
    }

    public function test_submitter_can_view_edit_page_for_submitted_request(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->categoryA->id,
            'title' => 'Flickering light tube in study area',
            'description' => 'Light fixture flickering continuously for 2 days.',
            'priority' => 'low',
            'status' => 'submitted',
            'building' => 'Main University Library',
            'wing_floor' => 'Floor 2',
            'room_number' => 'Quiet Study Room',
        ]);

        $response = $this->actingAs($this->student)->get(route('user.requests.edit', $serviceRequest->slug));

        $response->assertStatus(200);
        $response->assertSee('Modify Request: ' . $serviceRequest->tracking_code);
        $response->assertSee('Flickering light tube in study area');
    }

    public function test_submitter_can_update_submitted_request(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->categoryA->id,
            'title' => 'Typo in original title',
            'description' => 'Original description with missing room detail.',
            'priority' => 'low',
            'status' => 'submitted',
            'building' => 'Engineering Complex Block A',
            'wing_floor' => 'Floor 1',
            'room_number' => '101',
        ]);

        $response = $this->actingAs($this->student)->put(route('user.requests.update', $serviceRequest->slug), [
            'service_category_id' => $this->categoryB->id,
            'priority' => 'high',
            'building' => 'Engineering Complex Block A',
            'wing_floor' => 'Floor 3',
            'room_number' => 'Lab 305B',
            'title' => 'Corrected: Severe pipe leak in cleanroom',
            'description' => 'Pipe has burst under the main workstation and leaking water rapidly.',
        ]);

        $response->assertRedirect(route('user.requests.show', $serviceRequest->slug));
        $serviceRequest->refresh();

        $this->assertEquals('Corrected: Severe pipe leak in cleanroom', $serviceRequest->title);
        $this->assertEquals('Pipe has burst under the main workstation and leaking water rapidly.', $serviceRequest->description);
        $this->assertEquals('high', $serviceRequest->priority);
        $this->assertEquals($this->categoryB->id, $serviceRequest->service_category_id);
        $this->assertEquals('Lab 305B', $serviceRequest->room_number);
    }

    public function test_submitter_cannot_edit_request_once_assigned(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->categoryA->id,
            'title' => 'Damaged chair',
            'description' => 'Broken arm rest.',
            'priority' => 'low',
            'status' => 'assigned',
            'building' => 'Main University Library',
            'wing_floor' => 'Floor 1',
            'room_number' => '102',
        ]);

        // Attempt GET edit view
        $viewResponse = $this->actingAs($this->student)->get(route('user.requests.edit', $serviceRequest->slug));
        $viewResponse->assertStatus(403);

        // Attempt PUT update
        $updateResponse = $this->actingAs($this->student)->put(route('user.requests.update', $serviceRequest->slug), [
            'service_category_id' => $this->categoryA->id,
            'priority' => 'low',
            'building' => 'Main University Library',
            'wing_floor' => 'Floor 1',
            'room_number' => '102',
            'title' => 'Attempted modification after dispatch',
            'description' => 'Should be forbidden by policy.',
        ]);
        $updateResponse->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_edit_another_users_request(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->categoryA->id,
            'title' => 'Student A request',
            'description' => 'Private complaint lodged by Student A.',
            'priority' => 'low',
            'status' => 'submitted',
            'building' => 'Main University Library',
            'wing_floor' => 'Floor 1',
            'room_number' => '102',
        ]);

        $response = $this->actingAs($this->otherStudent)->get(route('user.requests.edit', $serviceRequest->slug));
        $response->assertStatus(403);

        $updateResponse = $this->actingAs($this->otherStudent)->put(route('user.requests.update', $serviceRequest->slug), [
            'service_category_id' => $this->categoryA->id,
            'priority' => 'low',
            'building' => 'Main University Library',
            'wing_floor' => 'Floor 1',
            'room_number' => '102',
            'title' => 'Tampered Title',
            'description' => 'Attempt to edit another students ticket.',
        ]);
        $updateResponse->assertStatus(403);
    }

    public function test_api_submitter_can_update_request_via_put_endpoint(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->categoryA->id,
            'title' => 'API lodged ticket',
            'description' => 'Original text via API.',
            'priority' => 'medium',
            'status' => 'submitted',
            'building' => 'Science & Research Center',
            'wing_floor' => 'Floor 2',
            'room_number' => '201',
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->putJson("/api/v1/requests/{$serviceRequest->slug}", [
            'title' => 'Updated title via API endpoint',
            'description' => 'Updated description with additional details via API.',
            'priority' => 'high',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.title', 'Updated title via API endpoint');

        $serviceRequest->refresh();
        $this->assertEquals('Updated title via API endpoint', $serviceRequest->title);
        $this->assertEquals('high', $serviceRequest->priority);
    }

    public function test_api_put_endpoint_rejects_unauthorized_or_assigned_tickets(): void
    {
        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->categoryA->id,
            'title' => 'Dispatched API ticket',
            'description' => 'Already assigned.',
            'priority' => 'medium',
            'status' => 'assigned',
            'building' => 'Science & Research Center',
            'wing_floor' => 'Floor 2',
            'room_number' => '201',
        ]);

        Sanctum::actingAs($this->student, ['*']);

        $response = $this->putJson("/api/v1/requests/{$serviceRequest->slug}", [
            'title' => 'Should fail because status is assigned',
        ]);

        $response->assertStatus(403);
    }
}
