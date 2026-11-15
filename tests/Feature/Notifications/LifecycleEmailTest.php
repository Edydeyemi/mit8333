<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Mail\OfficerAssignedNotificationMail;
use App\Mail\RequestSubmittedNotificationMail;
use App\Mail\TaskCompletedNotificationMail;
use App\Models\Admin\Role;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LifecycleEmailTest extends TestCase
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

        $this->officer = User::factory()->create([
            'role' => 'officer',
            'status' => 'active',
            'first_name' => 'Sarah',
            'last_name' => 'Jenkins',
            'email' => 'sarah_jenkins@headworld.edu',
        ]);
        $officerRole = Role::where('role_slug', 'officer')->first();
        if ($officerRole) {
            $this->officer->roles()->syncWithoutDetaching([$officerRole->id]);
        }

        $this->student = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'email' => 'alex_student@headworld.edu',
        ]);
        $userRole = Role::where('role_slug', 'user')->first();
        if ($userRole) {
            $this->student->roles()->syncWithoutDetaching([$userRole->id]);
        }

        $this->category = ServiceCategory::firstOrCreate(
            ['slug' => 'plumbing-water'],
            [
                'name' => 'Plumbing & Water',
                'description' => 'Pipes, leaks, drainage',
                'icon' => 'bi-droplet-half',
                'sla_hours' => 12,
                'is_active' => true,
            ]
        );
    }

    public function test_submitting_service_request_queues_email_notification(): void
    {
        Mail::fake();
        $this->actingAs($this->student);

        $response = $this->post(route('user.requests.store'), [
            'service_category_id' => $this->category->id,
            'priority' => 'high',
            'building' => "Queen's Hall",
            'wing_floor' => 'Wing B',
            'room_number' => 'Restroom 2',
            'title' => 'Burst pipe flooding restroom',
            'description' => 'Water actively leaking under main basin.',
        ]);

        $response->assertStatus(302);

        Mail::assertQueued(RequestSubmittedNotificationMail::class, function ($mail) {
            return $mail->serviceRequest->title === 'Burst pipe flooding restroom';
        });
    }

    public function test_assigning_officer_queues_email_notification(): void
    {
        Mail::fake();
        $this->actingAs($this->superAdmin);

        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'priority' => 'high',
            'building' => "Queen's Hall",
            'wing_floor' => 'Wing B',
            'room_number' => 'Restroom 2',
            'title' => 'Burst pipe flooding restroom',
            'description' => 'Water actively leaking under main basin.',
            'status' => 'submitted',
        ]);

        $response = $this->post(route('admin.requests.assign', $serviceRequest->slug), [
            'assigned_officer_id' => $this->officer->id,
            'notes' => 'Urgent plumbing triage required.',
        ]);

        $response->assertStatus(302);

        Mail::assertQueued(OfficerAssignedNotificationMail::class);
    }

    public function test_resolving_task_queues_completion_email_notification(): void
    {
        Mail::fake();
        $this->actingAs($this->officer);

        $serviceRequest = ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'assigned_officer_id' => $this->officer->id,
            'priority' => 'high',
            'building' => "Queen's Hall",
            'wing_floor' => 'Wing B',
            'room_number' => 'Restroom 2',
            'title' => 'Burst pipe flooding restroom',
            'description' => 'Water actively leaking under main basin.',
            'status' => 'in_progress',
        ]);

        $response = $this->post(route('facility.tasks.status', $serviceRequest->slug), [
            'status' => 'resolved',
            'notes' => 'P-trap replaced, compression coupling tightened and pressure tested.',
        ]);

        $response->assertStatus(302);

        Mail::assertQueued(TaskCompletedNotificationMail::class, function ($mail) {
            return $mail->serviceRequest->status === 'resolved';
        });
    }
}

