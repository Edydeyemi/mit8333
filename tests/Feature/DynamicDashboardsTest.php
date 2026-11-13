<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Role;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicDashboardsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $officer;
    protected User $student;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->superAdmin = User::where('role', 'super_admin')->first();

        $adminRole = Role::where('role_slug', 'admin')->first();
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'first_name' => 'Operations',
            'last_name' => 'Lead',
            'email' => 'ops_admin@headworld.edu',
        ]);
        if ($adminRole) {
            $this->admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $officerRole = Role::where('role_slug', 'officer')->first();
        $this->officer = User::factory()->create([
            'role' => 'officer',
            'status' => 'active',
            'first_name' => 'Lead',
            'last_name' => 'Technician',
            'email' => 'tech_lead@headworld.edu',
        ]);
        if ($officerRole) {
            $this->officer->roles()->syncWithoutDetaching([$officerRole->id]);
        }

        $this->student = User::where('role', 'user')->first();
        $this->category = ServiceCategory::first();
    }

    public function test_unauthenticated_users_are_redirected_to_login_for_all_dashboards(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/dashboard/data')->assertRedirect('/login');

        $this->get('/facility')->assertRedirect('/login');
        $this->get('/facility/dashboard/data')->assertRedirect('/login');

        $this->get('/portal')->assertRedirect('/login');
        $this->get('/portal/dashboard/data')->assertRedirect('/login');
    }

    public function test_admin_can_view_dashboard_and_receive_live_metrics_json(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');
        $response->assertStatus(200);
        $response->assertSee('Executive Facility Operations Command');
        $response->assertSee('adminDashboardPayload');
        $response->assertSee('chartRequestsTrend');
        $response->assertSee('chartCategoryDonut');
        $response->assertSee('chartOfficerPerf');

        $dataResponse = $this->actingAs($this->admin)->getJson('/admin/dashboard/data');
        $dataResponse->assertStatus(200);
        $dataResponse->assertJsonStructure([
            'kpis' => [
                'totalRequests',
                'activeDispatches',
                'emergencyCount',
                'unassignedCount',
                'slaComplianceRate',
            ],
            'trends' => [
                'labels',
                'logged',
                'resolved',
            ],
            'categories' => [
                'labels',
                'counts',
                'colors',
            ],
            'officerPerformance' => [
                'labels',
                'completed',
                'active',
            ],
            'recentAudits',
        ]);
    }

    public function test_unauthorized_resident_cannot_access_admin_dashboard_or_data(): void
    {
        $this->actingAs($this->student)->get('/admin')->assertStatus(403);
        $this->actingAs($this->student)->getJson('/admin/dashboard/data')->assertStatus(403);
    }

    public function test_officer_can_view_field_desk_and_receive_live_metrics_json(): void
    {
        $response = $this->actingAs($this->officer)->get('/facility');
        $response->assertStatus(200);
        $response->assertSee('Campus Facilities Field Desk');
        $response->assertSee('chartOfficerVelocity');
        $response->assertSee('chartOfficerCategories');
        $response->assertSee('officerDashboardPayload');

        $dataResponse = $this->actingAs($this->officer)->getJson('/facility/dashboard/data');
        $dataResponse->assertStatus(200);
        $dataResponse->assertJsonStructure([
            'kpis' => [
                'myAssigned',
                'myEmergency',
                'myResolved',
                'slaComplianceRate',
            ],
            'queue',
            'velocity' => [
                'labels',
                'assigned',
                'resolved',
            ],
            'categories' => [
                'labels',
                'counts',
                'colors',
            ],
        ]);
    }

    public function test_student_cannot_access_officer_dashboard(): void
    {
        $this->actingAs($this->student)->get('/facility')->assertStatus(403);
        $this->actingAs($this->student)->getJson('/facility/dashboard/data')->assertStatus(403);
    }

    public function test_resident_can_view_portal_dashboard_and_receive_live_metrics_json(): void
    {
        $response = $this->actingAs($this->student)->get('/portal');
        $response->assertStatus(200);
        $response->assertSee('Campus Resident Portal');
        $response->assertSee('chartUserStatus');
        $response->assertSee('chartUserCategories');
        $response->assertSee('userDashboardPayload');

        $dataResponse = $this->actingAs($this->student)->getJson('/portal/dashboard/data');
        $dataResponse->assertStatus(200);
        $dataResponse->assertJsonStructure([
            'kpis' => [
                'totalLogged',
                'inProgress',
                'resolved',
                'activeTickets',
                'emergencyCount',
            ],
            'recentRequests',
            'statusBreakdown' => [
                'labels',
                'counts',
                'colors',
            ],
            'categoryBreakdown' => [
                'labels',
                'counts',
                'colors',
            ],
        ]);
    }

    public function test_live_poller_reflects_new_emergency_assignment_for_officer(): void
    {
        // Initially officer has 0 emergency tickets
        $data1 = $this->actingAs($this->officer)->getJson('/facility/dashboard/data');
        $this->assertEquals(0, $data1->json('kpis.myEmergency'));
        $this->assertNull($data1->json('emergencyTicket'));

        // Assign an emergency ticket to this officer
        ServiceRequest::create([
            'user_id' => $this->student->id,
            'service_category_id' => $this->category->id,
            'title' => 'Critical Electrical Arcing in Science Lab',
            'description' => 'Dangerous spark observed in breaker box.',
            'building' => 'Science Complex',
            'wing_floor' => 'Floor 3',
            'room_number' => 'Lab 301',
            'priority' => 'emergency',
            'status' => 'assigned',
            'assigned_officer_id' => $this->officer->id,
            'assigned_at' => now(),
        ]);

        // Poller updates dynamically
        $data2 = $this->actingAs($this->officer)->getJson('/facility/dashboard/data');
        $this->assertEquals(1, $data2->json('kpis.myEmergency'));
        $this->assertNotNull($data2->json('emergencyTicket'));
        $this->assertEquals('Critical Electrical Arcing in Science Lab', $data2->json('emergencyTicket.title'));
    }
}
