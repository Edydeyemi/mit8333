<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $officer;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->superAdmin = User::where('role', 'super_admin')->first();

        // Create standard admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'first_name' => 'Dean',
            'last_name' => 'Sterling',
            'email' => 'dean_admin@headworld.edu',
        ]);
        $adminRole = Role::where('role_slug', 'admin')->first();
        if ($adminRole) {
            $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // Create officer
        $this->officer = User::factory()->create([
            'role' => 'officer',
            'status' => 'active',
            'first_name' => 'Marcus',
            'last_name' => 'Bennett',
            'email' => 'marcus_officer@headworld.edu',
        ]);
        $officerRole = Role::where('role_slug', 'officer')->first();
        if ($officerRole) {
            $this->officer->roles()->syncWithoutDetaching([$officerRole->id]);
        }

        // Create student / community user
        $this->student = User::factory()->create([
            'role' => 'user',
            'status' => 'active',
            'first_name' => 'Alexander',
            'last_name' => 'Cole',
            'email' => 'alex_student@headworld.edu',
        ]);
        $userRole = Role::where('role_slug', 'user')->first();
        if ($userRole) {
            $this->student->roles()->syncWithoutDetaching([$userRole->id]);
        }
    }

    public function test_student_sidebar_displays_only_resident_portal_links(): void
    {
        $response = $this->actingAs($this->student)->get(route('user.dashboard'));

        $response->assertStatus(200);

        // Permitted links
        $response->assertSee('Resident Portal');
        $response->assertSee('My Overview');
        $response->assertSee('Submit Request');
        $response->assertSee('My Request History');
        $response->assertSee('Update Profile Photo');

        // Forbidden links for regular students
        $response->assertDontSee('Operations Command');
        $response->assertDontSee('Executive Overview');
        $response->assertDontSee('Dispatch & Requests', false);
        $response->assertDontSee('Service Categories');
        $response->assertDontSee('User Directory');
        $response->assertDontSee('Roles & Permissions', false);
        $response->assertDontSee('Field Operations');
        $response->assertDontSee('Technician Desk');
    }

    public function test_officer_sidebar_displays_only_field_operations_links(): void
    {
        $response = $this->actingAs($this->officer)->get(route('facility.dashboard'));

        $response->assertStatus(200);

        // Permitted technician links
        $response->assertSee('Field Operations');
        $response->assertSee('Technician Desk');
        $response->assertSee('Tasks & Kanban', false);

        // Forbidden admin links
        $response->assertDontSee('Operations Command');
        $response->assertDontSee('Executive Overview');
        $response->assertDontSee('Dispatch & Requests', false);
        $response->assertDontSee('User Directory');
        $response->assertDontSee('Roles & Permissions', false);
        $response->assertDontSee('Resident Portal');
    }

    public function test_admin_sidebar_displays_operations_command_links(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Permitted operations links
        $response->assertSee('Operations Command');
        $response->assertSee('Executive Overview');
        $response->assertSee('Dispatch & Requests', false);
        $response->assertSee('Service Categories');
        $response->assertSee('User Directory');
        $response->assertSee('Roles & Permissions', false);
        $response->assertSee('Notifications Center');

        // Admin shouldn't see resident student self-service portal
        $response->assertDontSee('Resident Portal');
        $response->assertDontSee('Field Operations');
    }

    public function test_super_admin_has_omnipotent_access_to_management_sidebars(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // Super Admin sees operations command
        $response->assertSee('Operations Command');
        $response->assertSee('Executive Overview');
        $response->assertSee('Dispatch & Requests', false);
        $response->assertSee('Service Categories');
        $response->assertSee('User Directory');
        $response->assertSee('Roles & Permissions', false);
        $response->assertSee('Notifications Center');
    }
}
