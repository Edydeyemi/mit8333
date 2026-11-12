<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles
        $rolesData = [
            ['name' => 'Super Administrator', 'role_slug' => 'super_admin', 'created_by' => 'system'],
            ['name' => 'Campus Administrator', 'role_slug' => 'admin', 'created_by' => 'system'],
            ['name' => 'Data Entry Officer', 'role_slug' => 'data_entry', 'created_by' => 'system'],
            ['name' => 'Facility Maintenance Officer', 'role_slug' => 'officer', 'created_by' => 'system'],
            ['name' => 'Campus Resident / User', 'role_slug' => 'user', 'created_by' => 'system'],
        ];

        $rolesMap = [];
        foreach ($rolesData as $data) {
            $rolesMap[$data['role_slug']] = Role::firstOrCreate(
                ['role_slug' => $data['role_slug']],
                $data
            );
        }

        // 2. Seed Permissions from AppPermissions.php
        $permissionsConfig = file_exists(base_path('AppPermissions.php'))
            ? require base_path('AppPermissions.php')
            : ['permissions' => []];

        $permissions = $permissionsConfig['permissions'] ?? [];
        $permissionObjects = [];
        foreach ($permissions as $name => $description) {
            $permissionObjects[$name] = Permission::updateOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // 3. Assign Canonical Permissions to Roles
        $rolePermissionsMap = [
            'admin' => [
                'users.manage',
                'users.view',
                'officers.add',
                'officers.modify',
                'officers.suspend',
                'categories.manage',
                'categories.view',
                'roles.manage',
                'roles.view',
                'tasks.assign',
                'tasks.reassign',
                'tasks.cancel',
                'tasks.report',
                'tasks.view',
                'reports.generate',
                'reports.view',
                'notifications.view',
            ],
            'officer' => [
                'tasks.view',
                'tasks.update',
                'notifications.view',
            ],
            'data_entry' => [
                'tasks.report',
                'reports.view',
                'requests.create',
                'notifications.view',
            ],
            'user' => [
                'requests.create',
                'requests.view',
                'requests.cancel',
                'notifications.view',
            ],
        ];

        foreach ($rolePermissionsMap as $roleSlug => $permNames) {
            if (isset($rolesMap[$roleSlug])) {
                $ids = [];
                foreach ($permNames as $pName) {
                    if (isset($permissionObjects[$pName])) {
                        $ids[$permissionObjects[$pName]->id] = ['created_by' => 'system'];
                    }
                }
                $rolesMap[$roleSlug]->permissions()->sync($ids);
            }
        }

        // 4. Seed Primary Service Categories
        $categoriesData = [
            [
                'name' => 'Faulty Electricity & Lighting',
                'slug' => 'faulty-electricity-lighting',
                'icon' => 'bi-lightning-charge',
                'description' => 'Power failures, flickering lights, exposed conduits, faulty circuit breakers, and blown transformers.',
                'is_active' => true,
            ],
            [
                'name' => 'Damaged Furniture & Classroom Fixtures',
                'slug' => 'damaged-furniture-classroom-fixtures',
                'icon' => 'bi-door-closed',
                'description' => 'Broken lecture room seating, damaged whiteboards, misaligned classroom podiums, and broken door locks.',
                'is_active' => true,
            ],
            [
                'name' => 'Plumbing, Drainage & Water Leakage',
                'slug' => 'plumbing-drainage-water-leakage',
                'icon' => 'bi-droplet-half',
                'description' => 'Burst overhead piping, blocked restroom drainage, malfunctioning flush valves, and low water pressure.',
                'is_active' => true,
            ],
            [
                'name' => 'Wi-Fi, Network & IT Infrastructure',
                'slug' => 'wifi-network-it-infrastructure',
                'icon' => 'bi-wifi',
                'description' => 'Hostel wireless coverage outages, damaged Ethernet data ports, fiber conduit breaks, and switch malfunctions.',
                'is_active' => true,
            ],
            [
                'name' => 'Classroom & Laboratory Equipment Issues',
                'slug' => 'classroom-laboratory-equipment-issues',
                'icon' => 'bi-tools',
                'description' => 'Broken lab exhaust hoods, malfunctioning multimedia overhead projectors, and faulty HVAC air exchangers.',
                'is_active' => true,
            ],
            [
                'name' => 'Hostel & Residential Facility Maintenance',
                'slug' => 'hostel-residential-facility-maintenance',
                'icon' => 'bi-buildings',
                'description' => 'Damaged dormitory louvers, wardrobe repair, rooftop leaks, and student residential courtyard lighting.',
                'is_active' => true,
            ],
        ];

        foreach ($categoriesData as $cData) {
            ServiceCategory::firstOrCreate(
                ['slug' => $cData['slug']],
                $cData
            );
        }

        // 5. Seed Demo Users
        $defaultPassword = Hash::make('password');

        $users = [
            [
                'first_name' => 'Demo',
                'last_name' => 'User',
                'email' => 'user@in.com',
                'mobile' => '+805551234567',
                'password' => $defaultPassword,
                'role' => 'user',
                'status' => 'active',
                'slug' => 'alexander-cole-' . Str::lower(Str::random(6)),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Alexander',
                'last_name' => 'Cole',
                'email' => 'student@unilag.edu',
                'mobile' => '+15551234567',
                'password' => $defaultPassword,
                'role' => 'user',
                'status' => 'active',
                'slug' => 'alexander-cole-' . Str::lower(Str::random(6)),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Eleanor',
                'last_name' => 'Hughes',
                'email' => 'staff@unilag.edu',
                'mobile' => '+15551234568',
                'password' => $defaultPassword,
                'role' => 'user',
                'status' => 'active',
                'slug' => 'eleanor-hughes-' . Str::lower(Str::random(6)),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Marcus',
                'last_name' => 'Vance',
                'email' => 'officer@unilag.edu',
                'mobile' => '+15551234569',
                'password' => $defaultPassword,
                'role' => 'officer',
                'status' => 'active',
                'slug' => 'marcus-vance-' . Str::lower(Str::random(6)),
                'email_verified_at' => now(),
            ],
            [
                'first_name' => 'Sterling',
                'last_name' => 'Archer',
                'email' => 'admin@in.com',
                'mobile' => '+15551234570',
                'password' => $defaultPassword,
                'role' => 'super_admin',
                'status' => 'active',
                'slug' => 'sterling-archer-' . Str::lower(Str::random(6)),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Associate Role
            if (isset($rolesMap[$user->role])) {
                $user->roles()->syncWithoutDetaching([
                    $rolesMap[$user->role]->id => ['created_by' => 'system'],
                ]);
            }
        }
    }
}
