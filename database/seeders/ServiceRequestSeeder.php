<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestAssignment;
use App\Models\ServiceRequestAttachment;
use App\Models\ServiceRequestStatusUpdate;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ServiceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Fetch existing active users, officers, and admins from the database
        $requesters = User::where('role', 'user')->where('status', 'active')->get();
        $officers = User::where('role', 'officer')->where('status', 'active')->get();
        $admins = User::whereIn('role', ['super_admin', 'admin'])->where('status', 'active')->get();

        $demoUser = User::where('email', 'user@headworld.com')->first() ?? $requesters->first();
        $student = $requesters->first(fn (User $u): bool => str_contains($u->email, 'student')) ?? $requesters->first();
        $staff = $requesters->first(fn (User $u): bool => str_contains($u->email, 'staff')) ?? $requesters->skip(1)->first() ?? $student;
        $admin = User::where('email', 'edward@headworld.com')->first() ?? $admins->first();

        // 2. Fetch reference officers
        $officer1 = User::where('email', 'officer@headworld.com')->first() ?? $officers->first();
        $officer2 = User::where('email', 'officer2@headworld.com')->first() ?? $officers->skip(1)->first() ?? $officer1;
        $officer3 = User::where('email', 'officer3@headworld.com')->first() ?? $officers->skip(2)->first() ?? $officer1;

        if (!$student || !$officer1 || !$admin) {
            $this->command?->warn('Required users not found. Run DatabaseSeeder users step first.');
            return;
        }

        // 3. Fetch categories map
        $categories = ServiceCategory::all()->keyBy('slug');

        $electricalCat = $categories['faulty-electricity-lighting'] ?? ServiceCategory::first();
        $furnitureCat = $categories['damaged-furniture-classroom-fixtures'] ?? ServiceCategory::first();
        $plumbingCat = $categories['plumbing-drainage-water-leakage'] ?? ServiceCategory::first();
        $wifiCat = $categories['wifi-network-it-infrastructure'] ?? ServiceCategory::first();
        $equipmentCat = $categories['classroom-laboratory-equipment-issues'] ?? ServiceCategory::first();
        $hostelCat = $categories['hostel-residential-facility-maintenance'] ?? ServiceCategory::first();

        $now = Carbon::now();

        // 4. Seed structured dataset across users, officers, categories, and stages
        $requestsData = [
            // Stage: IN_PROGRESS (Emergency) - Student -> Officer 1
            [
                'slug' => 'sr-emergency-electrical-sparking-lab102',
                'tracking_code' => 'VRTS-2026-EL001',
                'user_id' => $student->id,
                'service_category_id' => $electricalCat->id,
                'title' => 'Sparks and Acrid Smoke from Central Distribution Board',
                'description' => 'Heavy electrical sparking and burning plastic odor emitting from the primary three-phase distribution box near the cold refrigeration units.',
                'priority' => 'emergency',
                'status' => 'in_progress',
                'building' => 'Science & Research Center',
                'wing_floor' => 'Ground Floor East Wing',
                'room_number' => 'Biochemistry Research Lab 102',
                'assigned_officer_id' => $officer1->id,
                'assigned_at' => $now->copy()->subHours(5),
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subHours(6),
                'updated_at' => $now->copy()->subHours(4),
                'evidence' => [
                    'file_name' => 'electrical_sparking_evidence.jpg',
                    'file_path' => 'evidence/photos/sample_sparking_board.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Immediate priority dispatch to Marcus Vance due to active fire risk.',
                        'created_at' => $now->copy()->subHours(5),
                    ],
                    [
                        'user_id' => $officer1->id,
                        'previous_status' => 'assigned',
                        'status' => 'in_progress',
                        'notes' => 'On-site: Isolated main breaker switch. Disassembling panel cover to test relay insulation breakdown.',
                        'created_at' => $now->copy()->subHours(4),
                    ],
                ],
            ],

            // Stage: ASSIGNED (Emergency) - Staff -> Officer 3
            [
                'slug' => 'sr-emergency-ceiling-pipe-burst-rm304',
                'tracking_code' => 'VRTS-2026-PL002',
                'user_id' => $staff->id,
                'service_category_id' => $plumbingCat->id,
                'title' => 'Major Overhead Pipe Burst Flooding Floor 3 Seminar Room',
                'description' => 'High-pressure ceiling pipe ruptured directly above the electronic podium. Water cascading into lighting fixtures and pooled across the parquet flooring.',
                'priority' => 'emergency',
                'status' => 'assigned',
                'building' => 'Engineering Complex Block A',
                'wing_floor' => 'Floor 3 West',
                'room_number' => 'Mechatronics Seminar Room 304',
                'assigned_officer_id' => $officer3->id,
                'assigned_at' => $now->copy()->subMinutes(45),
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subMinutes(60),
                'updated_at' => $now->copy()->subMinutes(45),
                'evidence' => [
                    'file_name' => 'water_leakage_photo.jpg',
                    'file_path' => 'evidence/photos/sample_water_leak.jpg',
                ],
                'assignments' => [
                    [
                        'officer_id' => $officer1->id,
                        'assigned_by_id' => $admin->id,
                        'assigned_at' => $now->copy()->subMinutes(55),
                        'unassigned_at' => $now->copy()->subMinutes(45),
                        'status' => 'reassigned',
                        'notes' => 'Initially routed to Officer Marcus Vance, then rerouted due to technician proximity.',
                    ],
                    [
                        'officer_id' => $officer3->id,
                        'assigned_by_id' => $admin->id,
                        'assigned_at' => $now->copy()->subMinutes(45),
                        'unassigned_at' => null,
                        'status' => 'active',
                        'notes' => 'Emergency plumbing dispatch. Officer Brenda Chisom assigned to shut down riser valve immediately.',
                    ],
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Emergency plumbing dispatch. Officer Brenda Chisom assigned to shut down riser valve immediately.',
                        'created_at' => $now->copy()->subMinutes(45),
                    ],
                ],
            ],

            // Stage: ON_HOLD (High Priority) - Student -> Officer 2
            [
                'slug' => 'sr-high-hostel-lighting-power-blackout',
                'tracking_code' => 'VRTS-2026-HS003',
                'user_id' => $student->id,
                'service_category_id' => $hostelCat->id,
                'title' => 'Total Power and Corridor Lighting Blackout in Block C',
                'description' => 'All hallway light fixtures and wall sockets are without electrical power following recent rainfall. Central sub-breaker trips immediately upon manual reset.',
                'priority' => 'high',
                'status' => 'on_hold',
                'building' => "Queen's Hall (Hostel)",
                'wing_floor' => 'Block C, 2nd Floor',
                'room_number' => 'Corridor & Rooms 210-218',
                'assigned_officer_id' => $officer2->id,
                'assigned_at' => $now->copy()->subDays(2),
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subDays(2)->subHours(3),
                'updated_at' => $now->copy()->subDay(),
                'evidence' => [
                    'file_name' => 'corridor_blackout.jpg',
                    'file_path' => 'evidence/photos/sample_blackout.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Assigned to Samuel Adebayo for electrical distribution troubleshooting.',
                        'created_at' => $now->copy()->subDays(2),
                    ],
                    [
                        'user_id' => $officer2->id,
                        'previous_status' => 'assigned',
                        'status' => 'in_progress',
                        'notes' => 'Conducted continuity checks: diagnosed ground fault inside sub-panel busbar.',
                        'created_at' => $now->copy()->subDays(2)->addHours(2),
                    ],
                    [
                        'user_id' => $officer2->id,
                        'previous_status' => 'in_progress',
                        'status' => 'on_hold',
                        'notes' => 'Work on hold pending receipt of replacement 63A triple pole miniature circuit breaker from central procurement.',
                        'created_at' => $now->copy()->subDay(),
                    ],
                ],
            ],

            // Stage: IN_PROGRESS (High Priority) - Staff -> Officer 1
            [
                'slug' => 'sr-high-wifi-network-switch-offline',
                'tracking_code' => 'VRTS-2026-IT004',
                'user_id' => $staff->id,
                'service_category_id' => $wifiCat->id,
                'title' => 'Core Wi-Fi Access Point and Network Switch Offline',
                'description' => 'Wireless coverage dropped completely in Lecture Theatre 1. The POE network distribution switch in the server cabinet displays solid amber fault LEDs.',
                'priority' => 'high',
                'status' => 'in_progress',
                'building' => 'Maxwell Lecture Theatres',
                'wing_floor' => 'Main Auditorium Wing',
                'room_number' => 'Lecture Theatre LT-1',
                'assigned_officer_id' => $officer1->id,
                'assigned_at' => $now->copy()->subHours(8),
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subHours(10),
                'updated_at' => $now->copy()->subHours(6),
                'evidence' => [
                    'file_name' => 'switch_fault_indicator.jpg',
                    'file_path' => 'evidence/photos/sample_switch_fault.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Assigned to Marcus Vance to inspect cabinet power supply and patch links.',
                        'created_at' => $now->copy()->subHours(8),
                    ],
                    [
                        'user_id' => $officer1->id,
                        'previous_status' => 'assigned',
                        'status' => 'in_progress',
                        'notes' => 'Accessing switch terminal. Running port diagnostics and testing fiber optic patch cables.',
                        'created_at' => $now->copy()->subHours(6),
                    ],
                ],
            ],

            // Stage: RESOLVED (Medium Priority) - Demo User -> Officer 1
            [
                'slug' => 'sr-med-detached-tiered-desk-rows',
                'tracking_code' => 'VRTS-2026-FN005',
                'user_id' => $demoUser->id,
                'service_category_id' => $furnitureCat->id,
                'title' => 'Detached Tiered Desk Rows 3 & 4 Fixed Seating',
                'description' => 'Anchor floor brackets along lecture row 3 have detached, causing desk surface and bench seat to tilt dangerously during classes.',
                'priority' => 'medium',
                'status' => 'resolved',
                'building' => 'Maxwell Lecture Theatres',
                'wing_floor' => 'Ground Level',
                'room_number' => 'Lecture Hall 05',
                'assigned_officer_id' => $officer1->id,
                'assigned_at' => $now->copy()->subDays(4),
                'resolved_at' => $now->copy()->subDays(2),
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(2),
                'evidence' => [
                    'file_name' => 'broken_desk_brackets.jpg',
                    'file_path' => 'evidence/photos/sample_broken_desk.jpg',
                ],
                'resolution' => [
                    'file_name' => 'desk_reinforcement_proof.jpg',
                    'file_path' => 'resolution/proofs/sample_repaired_desk.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Allocated to carpentry & fixture maintenance team.',
                        'created_at' => $now->copy()->subDays(4),
                    ],
                    [
                        'user_id' => $officer1->id,
                        'previous_status' => 'assigned',
                        'status' => 'in_progress',
                        'notes' => 'Procured heavy-duty floor expansion anchor sleeves and metal brackets.',
                        'created_at' => $now->copy()->subDays(3),
                    ],
                    [
                        'user_id' => $officer1->id,
                        'previous_status' => 'in_progress',
                        'status' => 'resolved',
                        'notes' => 'Installed 8 new concrete anchor bolts, secured frame, and verified weight load.',
                        'created_at' => $now->copy()->subDays(2),
                    ],
                ],
            ],

            // Stage: RESOLVED (Medium Priority) - Student -> Officer 3
            [
                'slug' => 'sr-med-drain-backflow-restroom-block-a',
                'tracking_code' => 'VRTS-2026-PL006',
                'user_id' => $student->id,
                'service_category_id' => $plumbingCat->id,
                'title' => 'Severe Drain Backflow and Overflowing Washbasins',
                'description' => 'Multiple sinks in the first floor library restrooms are blocked, causing wastewater to backflow onto tile floors.',
                'priority' => 'medium',
                'status' => 'resolved',
                'building' => 'Main University Library',
                'wing_floor' => '1st Floor East',
                'room_number' => 'Restroom Block A',
                'assigned_officer_id' => $officer3->id,
                'assigned_at' => $now->copy()->subDays(3),
                'resolved_at' => $now->copy()->subDay(),
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subDays(3)->subHours(4),
                'updated_at' => $now->copy()->subDay(),
                'evidence' => [
                    'file_name' => 'sink_blockage_photo.jpg',
                    'file_path' => 'evidence/photos/sample_sink_blockage.jpg',
                ],
                'resolution' => [
                    'file_name' => 'plumbing_cleared_proof.jpg',
                    'file_path' => 'resolution/proofs/sample_cleared_drain.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Assigned to Brenda Chisom for drainage clearance.',
                        'created_at' => $now->copy()->subDays(3),
                    ],
                    [
                        'user_id' => $officer3->id,
                        'previous_status' => 'assigned',
                        'status' => 'in_progress',
                        'notes' => 'Deployed rotary auger machine to clear central vertical waste stack.',
                        'created_at' => $now->copy()->subDays(2),
                    ],
                    [
                        'user_id' => $officer3->id,
                        'previous_status' => 'in_progress',
                        'status' => 'resolved',
                        'notes' => 'Obstruction removed, traps pressure-tested, and restroom floors disinfected.',
                        'created_at' => $now->copy()->subDay(),
                    ],
                ],
            ],

            // Stage: ASSIGNED (Low Priority) - Staff -> Officer 1
            [
                'slug' => 'sr-low-ac-blower-squeak-room412',
                'tracking_code' => 'VRTS-2026-EQ007',
                'user_id' => $staff->id,
                'service_category_id' => $equipmentCat->id,
                'title' => 'Split Unit AC Blower Squealing and Low Cooling Output',
                'description' => 'The wall-mounted air conditioner blower produces a high-frequency squeal when activated, with inadequate room temperature regulation.',
                'priority' => 'low',
                'status' => 'assigned',
                'building' => 'Senate & Administration Building',
                'wing_floor' => 'Level 4 North Wing',
                'room_number' => 'Faculty Records Registry 412',
                'assigned_officer_id' => $officer1->id,
                'assigned_at' => $now->copy()->subHours(12),
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subHours(24),
                'updated_at' => $now->copy()->subHours(12),
                'evidence' => [
                    'file_name' => 'ac_unit_tag.jpg',
                    'file_path' => 'evidence/photos/sample_ac_tag.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Assigned to HVAC technician Marcus Vance for routine filter and fan bearing service.',
                        'created_at' => $now->copy()->subHours(12),
                    ],
                ],
            ],

            // Stage: SUBMITTED (High Priority) - Student -> Unassigned
            [
                'slug' => 'sr-high-restroom-exhaust-fan-burnout',
                'tracking_code' => 'VRTS-2026-HS008',
                'user_id' => $student->id,
                'service_category_id' => $hostelCat->id,
                'title' => 'Motor Burnout on Restroom Ventilation System',
                'description' => 'Restroom ceiling exhaust fan stopped spinning with a burning electrical smell. Excessive humidity building up on tiles.',
                'priority' => 'high',
                'status' => 'submitted',
                'building' => 'Postgraduate Hall',
                'wing_floor' => 'Wing B Floor 1',
                'room_number' => 'Shared Facility Room 108',
                'assigned_officer_id' => null,
                'assigned_at' => null,
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subHours(2),
                'updated_at' => $now->copy()->subHours(2),
                'evidence' => [
                    'file_name' => 'exhaust_fan_housing.jpg',
                    'file_path' => 'evidence/photos/sample_exhaust_fan.jpg',
                ],
                'status_updates' => [],
            ],

            // Stage: SUBMITTED (Emergency) - Demo User -> Unassigned
            [
                'slug' => 'sr-emergency-exposed-armored-cable-walkway',
                'tracking_code' => 'VRTS-2026-EL009',
                'user_id' => $demoUser->id,
                'service_category_id' => $electricalCat->id,
                'title' => 'Exposed Armored Cable with Damaged Insulation near Workshop',
                'description' => 'Delivery machinery scraped the exterior wall conduit, ripping protective sheathing and exposing high voltage electrical conductors beside student pathway.',
                'priority' => 'emergency',
                'status' => 'submitted',
                'building' => 'Engineering Complex Block A',
                'wing_floor' => 'Exterior Covered Walkway',
                'room_number' => 'Adjacent to Workshop 1',
                'assigned_officer_id' => null,
                'assigned_at' => null,
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subMinutes(30),
                'updated_at' => $now->copy()->subMinutes(30),
                'evidence' => [
                    'file_name' => 'exposed_cable_hazard.jpg',
                    'file_path' => 'evidence/photos/sample_exposed_cable.jpg',
                ],
                'status_updates' => [],
            ],

            // Stage: ASSIGNED (Medium Priority) - Staff -> Officer 2
            [
                'slug' => 'sr-med-damaged-hdmi-wallplate-c201',
                'tracking_code' => 'VRTS-2026-EQ010',
                'user_id' => $staff->id,
                'service_category_id' => $equipmentCat->id,
                'title' => 'Damaged HDMI Wall Plate and Ceiling Projector Malfunction',
                'description' => 'Wall HDMI female jack has broken copper pins causing video disconnection during faculty lectures. Overhead projector IR receiver is unresponsive.',
                'priority' => 'medium',
                'status' => 'assigned',
                'building' => 'Science & Research Center',
                'wing_floor' => 'Floor 2 Central',
                'room_number' => 'Lecture Theatre C-201',
                'assigned_officer_id' => $officer2->id,
                'assigned_at' => $now->copy()->subHours(18),
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subHours(22),
                'updated_at' => $now->copy()->subHours(18),
                'evidence' => [
                    'file_name' => 'broken_hdmi_socket.jpg',
                    'file_path' => 'evidence/photos/sample_hdmi_socket.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Assigned to Samuel Adebayo to rewire AV wall plate and replace remote batteries.',
                        'created_at' => $now->copy()->subHours(18),
                    ],
                ],
            ],

            // Stage: CANCELLED (Low Priority) - Student -> Unassigned
            [
                'slug' => 'sr-low-study-cubicle-door-lock-jammed',
                'tracking_code' => 'VRTS-2026-FN011',
                'user_id' => $student->id,
                'service_category_id' => $furnitureCat->id,
                'title' => 'Mortise Lock Cylinder Sticking on Study Cubicle Door',
                'description' => 'Key sticks inside the brass lock cylinder and requires excessive wiggling to turn latch.',
                'priority' => 'low',
                'status' => 'cancelled',
                'building' => 'Main University Library',
                'wing_floor' => 'Basement Level',
                'room_number' => 'Quiet Study Room B-04',
                'assigned_officer_id' => null,
                'assigned_at' => null,
                'resolved_at' => null,
                'cancelled_at' => $now->copy()->subDays(3),
                'cancellation_reason' => 'Issue resolved directly by library floor custodial staff using silicone lubricant spray.',
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(3),
                'evidence' => [
                    'file_name' => 'study_door_lock.jpg',
                    'file_path' => 'evidence/photos/sample_door_lock.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $student->id,
                        'previous_status' => 'submitted',
                        'status' => 'cancelled',
                        'notes' => 'Requester cancelled ticket: Issue resolved by library custodial staff.',
                        'created_at' => $now->copy()->subDays(3),
                    ],
                ],
            ],

            // Stage: ON_HOLD (Medium Priority) - Demo User -> Officer 2
            [
                'slug' => 'sr-med-perimeter-security-floodlights-out',
                'tracking_code' => 'VRTS-2026-EL012',
                'user_id' => $demoUser->id,
                'service_category_id' => $electricalCat->id,
                'title' => 'Perimeter Security Floodlights Non-Functional',
                'description' => 'Two 200W high-mast LED floodlights along the sports pavilion pedestrian walkway are completely inoperative at night.',
                'priority' => 'medium',
                'status' => 'on_hold',
                'building' => 'Sports & Recreation Pavilion',
                'wing_floor' => 'Outdoor Perimeters',
                'room_number' => 'Field Access Gate 2',
                'assigned_officer_id' => $officer2->id,
                'assigned_at' => $now->copy()->subDays(3),
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subDays(4),
                'updated_at' => $now->copy()->subDays(2),
                'evidence' => [
                    'file_name' => 'floodlight_pole.jpg',
                    'file_path' => 'evidence/photos/sample_floodlight.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Assigned to Samuel Adebayo for high-mast light inspection.',
                        'created_at' => $now->copy()->subDays(3),
                    ],
                    [
                        'user_id' => $officer2->id,
                        'previous_status' => 'assigned',
                        'status' => 'in_progress',
                        'notes' => 'Inspected pole control box. Internal LED driver module is burnt out.',
                        'created_at' => $now->copy()->subDays(3)->addHours(4),
                    ],
                    [
                        'user_id' => $officer2->id,
                        'previous_status' => 'in_progress',
                        'status' => 'on_hold',
                        'notes' => 'Work on hold pending scheduled arrival of hydraulic bucket truck from Physical Works Department.',
                        'created_at' => $now->copy()->subDays(2),
                    ],
                ],
            ],

            // Stage: IN_PROGRESS (High Priority) - Staff -> Officer 3
            [
                'slug' => 'sr-high-chemical-fume-hood-sensor-alarm',
                'tracking_code' => 'VRTS-2026-EQ013',
                'user_id' => $staff->id,
                'service_category_id' => $equipmentCat->id,
                'title' => 'Face Velocity Alarm Triggering on Chemical Extraction Hood',
                'description' => 'Laboratory chemical fume hood 2 display screen emits continuous audio alert indicating airflow face velocity is sub-standard (below 0.35 m/s).',
                'priority' => 'high',
                'status' => 'in_progress',
                'building' => 'Science & Research Center',
                'wing_floor' => '3rd Floor Organic Chemistry Wing',
                'room_number' => 'Lab 310 Fume Hood 2',
                'assigned_officer_id' => $officer3->id,
                'assigned_at' => $now->copy()->subHours(16),
                'resolved_at' => null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subHours(20),
                'updated_at' => $now->copy()->subHours(10),
                'evidence' => [
                    'file_name' => 'fume_hood_sensor_error.jpg',
                    'file_path' => 'evidence/photos/sample_fume_hood.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Assigned to Brenda Chisom for ventilation and ductwork inspection.',
                        'created_at' => $now->copy()->subHours(16),
                    ],
                    [
                        'user_id' => $officer3->id,
                        'previous_status' => 'assigned',
                        'status' => 'in_progress',
                        'notes' => 'Inspecting rooftop extract fan belts and variable air volume (VAV) damper position.',
                        'created_at' => $now->copy()->subHours(10),
                    ],
                ],
            ],

            // Stage: RESOLVED (Low Priority) - Student -> Officer 1
            [
                'slug' => 'sr-low-hostel-window-louver-latches-room104',
                'tracking_code' => 'VRTS-2026-HS014',
                'user_id' => $student->id,
                'service_category_id' => $hostelCat->id,
                'title' => 'Loose Glass Louver Blades and Broken Aluminum Clip Handles',
                'description' => 'Two glass window panes slipped loose from their aluminum brackets during wind gust. Tension clips are stripped.',
                'priority' => 'low',
                'status' => 'resolved',
                'building' => "Queen's Hall (Hostel)",
                'wing_floor' => 'Block A, 1st Floor',
                'room_number' => 'Room 104',
                'assigned_officer_id' => $officer1->id,
                'assigned_at' => $now->copy()->subDays(5),
                'resolved_at' => $now->copy()->subDays(3),
                'cancelled_at' => null,
                'cancellation_reason' => null,
                'created_at' => $now->copy()->subDays(6),
                'updated_at' => $now->copy()->subDays(3),
                'evidence' => [
                    'file_name' => 'loose_louvers.jpg',
                    'file_path' => 'evidence/photos/sample_louver.jpg',
                ],
                'resolution' => [
                    'file_name' => 'louver_repaired_proof.jpg',
                    'file_path' => 'resolution/proofs/sample_repaired_louver.jpg',
                ],
                'status_updates' => [
                    [
                        'user_id' => $admin->id,
                        'previous_status' => 'submitted',
                        'status' => 'assigned',
                        'notes' => 'Assigned to Marcus Vance.',
                        'created_at' => $now->copy()->subDays(5),
                    ],
                    [
                        'user_id' => $officer1->id,
                        'previous_status' => 'assigned',
                        'status' => 'in_progress',
                        'notes' => 'Procured new louver channels and rubber weather-strips.',
                        'created_at' => $now->copy()->subDays(4),
                    ],
                    [
                        'user_id' => $officer1->id,
                        'previous_status' => 'in_progress',
                        'status' => 'resolved',
                        'notes' => 'Installed new aluminum clips, reseated panes securely, and verified smooth opening action.',
                        'created_at' => $now->copy()->subDays(3),
                    ],
                ],
            ],
        ];

        // 5. Execute DB persistence
        foreach ($requestsData as $item) {
            $evidenceData = $item['evidence'] ?? null;
            $resolutionData = $item['resolution'] ?? null;
            $statusUpdatesData = $item['status_updates'] ?? [];
            $assignmentsData = $item['assignments'] ?? null;

            unset($item['evidence'], $item['resolution'], $item['status_updates'], $item['assignments']);

            $request = ServiceRequest::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );

            // Seed initial evidence attachment
            if ($evidenceData) {
                ServiceRequestAttachment::updateOrCreate(
                    [
                        'service_request_id' => $request->id,
                        'type' => 'evidence',
                    ],
                    [
                        'file_name' => $evidenceData['file_name'],
                        'file_path' => $evidenceData['file_path'],
                        'file_type' => 'image/jpeg',
                        'file_size' => 1024 * 1024 * 2, // 2MB
                        'uploaded_by' => $request->user_id,
                        'created_at' => $request->created_at,
                        'updated_at' => $request->created_at,
                    ]
                );
            }

            // Seed progression status updates
            $lastStatusUpdateId = null;
            foreach ($statusUpdatesData as $su) {
                $statusRecord = ServiceRequestStatusUpdate::updateOrCreate(
                    [
                        'service_request_id' => $request->id,
                        'status' => $su['status'],
                        'previous_status' => $su['previous_status'],
                    ],
                    [
                        'user_id' => $su['user_id'],
                        'notes' => $su['notes'],
                        'created_at' => $su['created_at'],
                        'updated_at' => $su['created_at'],
                    ]
                );
                $lastStatusUpdateId = $statusRecord->id;
            }

            // Seed resolution proof attachment if resolved
            if ($resolutionData && $request->status === 'resolved') {
                ServiceRequestAttachment::updateOrCreate(
                    [
                        'service_request_id' => $request->id,
                        'type' => 'resolution_proof',
                    ],
                    [
                        'status_update_id' => $lastStatusUpdateId,
                        'file_name' => $resolutionData['file_name'],
                        'file_path' => $resolutionData['file_path'],
                        'file_type' => 'image/jpeg',
                        'file_size' => 1024 * 850,
                        'uploaded_by' => $request->assigned_officer_id ?? $admin->id,
                        'created_at' => $request->resolved_at ?? $now,
                        'updated_at' => $request->resolved_at ?? $now,
                    ]
                );
            }

            // Seed dedicated assignments entity records
            if (!empty($assignmentsData)) {
                foreach ($assignmentsData as $aData) {
                    $slug = 'sra-' . $request->slug . '-' . $aData['officer_id'] . '-' . ($aData['status'] ?? 'active');
                    ServiceRequestAssignment::updateOrCreate(
                        [
                            'slug' => $slug,
                        ],
                        [
                            'service_request_id' => $request->id,
                            'officer_id' => $aData['officer_id'],
                            'assigned_by_id' => $aData['assigned_by_id'] ?? $admin->id,
                            'assigned_at' => $aData['assigned_at'],
                            'unassigned_at' => $aData['unassigned_at'] ?? null,
                            'status' => $aData['status'] ?? 'active',
                            'notes' => $aData['notes'] ?? null,
                            'created_at' => $aData['assigned_at'] ?? $request->created_at,
                            'updated_at' => $aData['updated_at'] ?? $now,
                        ]
                    );
                }
            } elseif ($request->assigned_officer_id) {
                $assignmentStatus = match ($request->status) {
                    'resolved' => 'completed',
                    'cancelled' => 'cancelled',
                    default => 'active',
                };

                $assignedNotes = null;
                foreach ($statusUpdatesData as $su) {
                    if ($su['status'] === 'assigned') {
                        $assignedNotes = $su['notes'] ?? null;
                        break;
                    }
                }

                $slug = 'sra-' . $request->slug . '-' . $request->assigned_officer_id;
                ServiceRequestAssignment::updateOrCreate(
                    [
                        'slug' => $slug,
                    ],
                    [
                        'service_request_id' => $request->id,
                        'officer_id' => $request->assigned_officer_id,
                        'assigned_by_id' => $admin->id,
                        'assigned_at' => $request->assigned_at ?? $request->created_at,
                        'unassigned_at' => in_array($assignmentStatus, ['cancelled', 'completed'], true)
                            ? ($request->resolved_at ?? $request->cancelled_at)
                            : null,
                        'status' => $assignmentStatus,
                        'notes' => $assignedNotes ?? 'Dispatched by university operations console.',
                        'created_at' => $request->assigned_at ?? $request->created_at,
                        'updated_at' => $request->updated_at ?? $now,
                    ]
                );
            }
        }

        // 6. Seed 120 Faker-generated requests pulling existing DB users & officers
        $requesters = User::where('role', 'user')->where('status', 'active')->get();
        $officers = User::where('role', 'officer')->where('status', 'active')->get();
        $admins = User::whereIn('role', ['super_admin', 'admin'])->where('status', 'active')->get();
        $activeCategories = ServiceCategory::where('is_active', true)->get();

        if ($requesters->isEmpty() || $officers->isEmpty() || $admins->isEmpty() || $activeCategories->isEmpty()) {
            $this->command?->warn('Insufficient active users, officers, admins, or categories for Faker service requests.');
            return;
        }

        try {
            $this->seedFakerRequests($requesters, $officers, $admins, $activeCategories);
        } finally {
            Auth::logout();
        }
    }

    /**
     * Generate 120 realistic service requests across all closure stages with reassignments and audit logs.
     *
     * @param  Collection<int, User>  $requesters
     * @param  Collection<int, User>  $officers
     * @param  Collection<int, User>  $admins
     * @param  Collection<int, ServiceCategory>  $categories
     */
    private function seedFakerRequests(
        Collection $requesters,
        Collection $officers,
        Collection $admins,
        Collection $categories
    ): void {
        $faker = FakerFactory::create('en_NG');

        $stagePlan = [
            'submitted' => 15,
            'assigned' => 18,
            'in_progress' => 22,
            'on_hold' => 12,
            'resolved' => 35,
            'cancelled' => 10,
            'rejected' => 8,
        ];

        $buildings = [
            'Senate Building',
            'Faculty of Engineering',
            'Faculty of Science',
            'Main University Library',
            'ICT & CBT Center',
            'Moremi Hall (Female Hostel)',
            'Jaja Hall (Male Hostel)',
            'Mariere Hall (Male Hostel)',
            'Makama Bida Hall',
            'Faculty of Law',
            'College of Medicine Annex',
            'New Lecture Theatre Complex',
        ];

        $wings = [
            'Ground Floor East Wing',
            'Ground Floor West Wing',
            '1st Floor North Block',
            '2nd Floor South Wing',
            '3rd Floor Central Corridor',
            'Annex Block B',
        ];

        $priorities = ['low', 'medium', 'high', 'emergency'];

        $categoryTemplates = [
            'faulty-electricity-lighting' => [
                'Power Outage and Tripped Breaker in ',
                'Flickering Fluorescent Fixtures in ',
                'Exposed Wall Socket Conduit in ',
                'Damaged Distribution Panel Switch in ',
            ],
            'damaged-furniture-classroom-fixtures' => [
                'Broken Lecture Hall Desks and Chairs in ',
                'Detached Whiteboard Frame in ',
                'Damaged Entrance Door Lock and Hinges in ',
                'Broken Auditorium Seating Row in ',
            ],
            'plumbing-drainage-water-leakage' => [
                'Burst Overhead Water Pipe in ',
                'Blocked Restroom Floor Drain in ',
                'Leaking Sink Valve and Cistern in ',
                'Low Water Pressure Across Washrooms in ',
            ],
            'wifi-network-it-infrastructure' => [
                'Campus Wi-Fi Access Point Offline in ',
                'Damaged Ethernet Wall Port in ',
                'Intermittent Network Switch Dropout in ',
                'Fiber Patch Cord Fault in ',
            ],
            'classroom-laboratory-equipment-issues' => [
                'Malfunctioning Overhead Projector in ',
                'Faulty Lab Fume Extractor Hood in ',
                'Split-Unit AC Not Cooling in ',
                'Broken Podium Audio Amplifier in ',
            ],
            'hostel-residential-facility-maintenance' => [
                'Damaged Hostel Window Louvers in ',
                'Broken Wardrobe Hinge and Latch in ',
                'Courtyard Security Floodlight Outage in ',
                'Ceiling Water Seepage After Rain in ',
            ],
        ];

        foreach ($stagePlan as $targetStage => $count) {
            for ($i = 0; $i < $count; $i++) {
                $requester = $requesters->random();
                $admin = $admins->random();
                $category = $categories->random();
                $building = $faker->randomElement($buildings);
                $wing = $faker->randomElement($wings);
                $room = 'Room ' . $faker->numberBetween(101, 420);

                $templates = $categoryTemplates[$category->slug] ?? ['Facility Maintenance Issue Reported in '];
                $title = $faker->randomElement($templates) . $building . ' (' . $room . ')';

                $this->createRequestWithLifecycle(
                    $faker,
                    $requester,
                    $admin,
                    $officers,
                    $category,
                    $targetStage,
                    $faker->randomElement($priorities),
                    $title,
                    $building,
                    $wing,
                    $room
                );
            }
        }
    }

    /**
     * Create a single service request and step it through its lifecycle so Auditable logs each actor & transition.
     *
     * @param  Collection<int, User>  $officers
     */
    private function createRequestWithLifecycle(
        Generator $faker,
        User $requester,
        User $admin,
        Collection $officers,
        ServiceCategory $category,
        string $targetStage,
        string $priority,
        string $title,
        string $building,
        string $wing,
        string $room
    ): void {
        $createdAt = Carbon::now()->subDays($faker->numberBetween(1, 45))->subHours($faker->numberBetween(0, 23));

        // Step 1: Requester submits the ticket
        Auth::setUser($requester);
        $request = ServiceRequest::create([
            'slug' => 'sr-' . Str::uuid(),
            'user_id' => $requester->id,
            'service_category_id' => $category->id,
            'title' => $title,
            'description' => $faker->paragraph(2) . ' Reported at ' . $building . ', ' . $wing . ', ' . $room . '.',
            'priority' => $priority,
            'status' => 'submitted',
            'building' => $building,
            'wing_floor' => $wing,
            'room_number' => $room,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        if ($faker->boolean(70)) {
            ServiceRequestAttachment::create([
                'service_request_id' => $request->id,
                'file_name' => 'evidence_' . Str::lower(Str::random(8)) . '.jpg',
                'file_path' => 'evidence/photos/' . Str::uuid() . '.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => $faker->numberBetween(250_000, 3_500_000),
                'type' => 'evidence',
                'uploaded_by' => $requester->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        if ($targetStage === 'submitted') {
            return;
        }

        // Step 2: Rejected directly by Admin
        if ($targetStage === 'rejected') {
            $rejectedAt = $createdAt->copy()->addHours($faker->numberBetween(2, 24));
            Auth::setUser($admin);
            $request->update([
                'status' => 'rejected',
                'updated_at' => $rejectedAt,
            ]);

            $this->recordStatusUpdate(
                $request->id,
                $admin->id,
                'submitted',
                'rejected',
                $faker->randomElement([
                    'Duplicate request already logged for this location.',
                    'Out of facility maintenance scope; referred to ICT/Asset Registry.',
                    'Insufficient location details provided; please resubmit with room number.',
                ]),
                $rejectedAt
            );

            return;
        }

        // Some cancelled tickets are cancelled before assignment
        $assignBeforeCancel = $targetStage === 'cancelled' && $faker->boolean(50);
        if ($targetStage === 'cancelled' && !$assignBeforeCancel) {
            $cancelledAt = $createdAt->copy()->addHours($faker->numberBetween(1, 12));
            $reason = $faker->randomElement([
                'Issue resolved on its own before dispatch.',
                'Submitted under wrong building wing by mistake.',
                'No longer required; class relocated.',
            ]);

            Auth::setUser($requester);
            $request->update([
                'status' => 'cancelled',
                'cancelled_at' => $cancelledAt,
                'cancellation_reason' => $reason,
                'updated_at' => $cancelledAt,
            ]);

            $this->recordStatusUpdate($request->id, $requester->id, 'submitted', 'cancelled', $reason, $cancelledAt);

            return;
        }

        // Step 3: Admin assigns Officer A
        $assignedAt = $createdAt->copy()->addHours($faker->numberBetween(1, 8));
        $officerA = $officers->random();

        Auth::setUser($admin);
        $request->update([
            'status' => 'assigned',
            'assigned_officer_id' => $officerA->id,
            'assigned_at' => $assignedAt,
            'updated_at' => $assignedAt,
        ]);

        $activeAssignment = ServiceRequestAssignment::create([
            'service_request_id' => $request->id,
            'officer_id' => $officerA->id,
            'assigned_by_id' => $admin->id,
            'assigned_at' => $assignedAt,
            'status' => 'active',
            'notes' => 'Initial dispatch to ' . $officerA->name . '.',
            'created_at' => $assignedAt,
            'updated_at' => $assignedAt,
        ]);

        $this->recordStatusUpdate(
            $request->id,
            $admin->id,
            'submitted',
            'assigned',
            'Dispatched to ' . $officerA->name . ' for inspection and remediation.',
            $assignedAt
        );

        $currentOfficer = $officerA;
        $lastTransitionAt = $assignedAt;

        // Step 4: Optional Reassignment (~30% of assigned+ tickets)
        if ($targetStage !== 'cancelled' && $officers->count() > 1 && $faker->boolean(30)) {
            $reassignAt = $assignedAt->copy()->addHours($faker->numberBetween(1, 6));
            $officerB = $officers->where('id', '!=', $officerA->id)->random();

            Auth::setUser($admin);
            $activeAssignment->update([
                'status' => 'reassigned',
                'unassigned_at' => $reassignAt,
                'notes' => 'Reassigned due to workload balancing / shift handover.',
                'updated_at' => $reassignAt,
            ]);

            $activeAssignment = ServiceRequestAssignment::create([
                'service_request_id' => $request->id,
                'officer_id' => $officerB->id,
                'assigned_by_id' => $admin->id,
                'assigned_at' => $reassignAt,
                'status' => 'active',
                'notes' => 'Reassigned from ' . $officerA->name . ' to ' . $officerB->name . '.',
                'created_at' => $reassignAt,
                'updated_at' => $reassignAt,
            ]);

            $request->update([
                'assigned_officer_id' => $officerB->id,
                'assigned_at' => $reassignAt,
                'updated_at' => $reassignAt,
            ]);

            $this->recordStatusUpdate(
                $request->id,
                $admin->id,
                'assigned',
                'assigned',
                'Ticket reassigned from ' . $officerA->name . ' to ' . $officerB->name . '.',
                $reassignAt
            );

            $currentOfficer = $officerB;
            $lastTransitionAt = $reassignAt;
        }

        // Cancelled after assignment
        if ($targetStage === 'cancelled') {
            $cancelledAt = $lastTransitionAt->copy()->addHours($faker->numberBetween(1, 10));
            $reason = 'Requester cancelled ticket after initial dispatch.';

            Auth::setUser($requester);
            $request->update([
                'status' => 'cancelled',
                'cancelled_at' => $cancelledAt,
                'cancellation_reason' => $reason,
                'updated_at' => $cancelledAt,
            ]);

            $activeAssignment->update([
                'status' => 'cancelled',
                'unassigned_at' => $cancelledAt,
                'updated_at' => $cancelledAt,
            ]);

            $this->recordStatusUpdate($request->id, $requester->id, 'assigned', 'cancelled', $reason, $cancelledAt);

            return;
        }

        if ($targetStage === 'assigned') {
            return;
        }

        // Step 5: Officer moves ticket to In Progress
        $inProgressAt = $lastTransitionAt->copy()->addHours($faker->numberBetween(1, 8));
        Auth::setUser($currentOfficer);
        $request->update([
            'status' => 'in_progress',
            'updated_at' => $inProgressAt,
        ]);

        $this->recordStatusUpdate(
            $request->id,
            $currentOfficer->id,
            'assigned',
            'in_progress',
            'Officer arrived on site and commenced diagnostic/repair work.',
            $inProgressAt
        );

        if ($targetStage === 'in_progress') {
            return;
        }

        // Step 6: On Hold (either final stage or intermediate before resolution)
        $previousStage = 'in_progress';
        $currentTime = $inProgressAt;

        if ($targetStage === 'on_hold' || ($targetStage === 'resolved' && $faker->boolean(25))) {
            $onHoldAt = $inProgressAt->copy()->addHours($faker->numberBetween(2, 12));
            Auth::setUser($currentOfficer);
            $request->update([
                'status' => 'on_hold',
                'updated_at' => $onHoldAt,
            ]);

            $this->recordStatusUpdate(
                $request->id,
                $currentOfficer->id,
                'in_progress',
                'on_hold',
                $faker->randomElement([
                    'Awaiting replacement spare parts from central stores.',
                    'Scheduled for after-hours power isolation window.',
                    'Pending vendor component delivery.',
                ]),
                $onHoldAt
            );

            $previousStage = 'on_hold';
            $currentTime = $onHoldAt;

            if ($targetStage === 'on_hold') {
                return;
            }
        }

        // Step 7: Resolved
        $resolvedAt = $currentTime->copy()->addHours($faker->numberBetween(2, 24));
        Auth::setUser($currentOfficer);
        $request->update([
            'status' => 'resolved',
            'resolved_at' => $resolvedAt,
            'updated_at' => $resolvedAt,
        ]);

        $activeAssignment->update([
            'status' => 'completed',
            'unassigned_at' => $resolvedAt,
            'updated_at' => $resolvedAt,
        ]);

        $resolutionUpdate = $this->recordStatusUpdate(
            $request->id,
            $currentOfficer->id,
            $previousStage,
            'resolved',
            'Fault fully rectified, safety-tested, and area restored to normal operation.',
            $resolvedAt
        );

        if ($faker->boolean(80)) {
            ServiceRequestAttachment::create([
                'service_request_id' => $request->id,
                'status_update_id' => $resolutionUpdate->id,
                'file_name' => 'resolution_' . Str::lower(Str::random(8)) . '.jpg',
                'file_path' => 'evidence/resolutions/' . Str::uuid() . '.jpg',
                'file_type' => 'image/jpeg',
                'file_size' => $faker->numberBetween(250_000, 2_500_000),
                'type' => 'resolution_proof',
                'uploaded_by' => $currentOfficer->id,
                'created_at' => $resolvedAt,
                'updated_at' => $resolvedAt,
            ]);
        }
    }

    /**
     * Persist a ServiceRequestStatusUpdate record.
     */
    private function recordStatusUpdate(
        int $serviceRequestId,
        int $userId,
        ?string $previousStatus,
        string $status,
        string $notes,
        Carbon $timestamp
    ): ServiceRequestStatusUpdate {
        return ServiceRequestStatusUpdate::create([
            'service_request_id' => $serviceRequestId,
            'user_id' => $userId,
            'previous_status' => $previousStatus,
            'status' => $status,
            'notes' => $notes,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}

