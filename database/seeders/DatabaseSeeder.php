<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
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

        // 2. Seed Permissions
        $permissionsConfig = file_exists(base_path('AppPermissions.php'))
            ? require base_path('AppPermissions.php')
            : ['permissions' => []];

        $permissions = $permissionsConfig['permissions'] ?? [];
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['name' => $name, 'description' => $description]
            );
        }

        // 3. Seed Demo Users
        $defaultPassword = Hash::make('Headworld2026!');

        $users = [
            [
                'first_name' => 'Alexander',
                'last_name' => 'Cole',
                'email' => 'student@mit.edu',
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
                'email' => 'staff@mit.edu',
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
                'email' => 'officer@mit.edu',
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
                'email' => 'admin@mit.edu',
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
                    $rolesMap[$user->role]->id => ['created_by' => 'system']
                ]);
            }
        }
    }
}
