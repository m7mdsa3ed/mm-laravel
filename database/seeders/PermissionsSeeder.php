<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /** Run the database seeds. */
    public function run(): void
    {
        $guard = 'web';

        $permissions = [
            'settings' => [
                'update',
            ],

            'currencies' => [
                'show',
                'update',
                'create',
                'delete',
            ],
        ];

        $permissions = collect(Arr::dot($permissions))
            ->map(function ($value, $key) {
                $parts = explode('.', $key);

                $lastPart = end($parts);

                $isNumber = is_numeric($lastPart);

                if ($isNumber) {
                    $key = str_replace($lastPart, '', $key);
                }

                return $key . $value;
            })
            ->values();

        $roles = [
            'manager' => $permissions,
            'user' => [],
        ];

        foreach ($roles as $role => $permissions) {
            $role = Role::query()
                ->updateOrcreate([
                    'name' => $role,
                    'guard_name' => $guard,
                ]);

            foreach ($permissions as $permissionName) {
                $permission = Permission::query()
                    ->updateOrcreate([
                        'name' => $permissionName,
                        'guard_name' => $guard,
                    ]);

                $permission->assignRole($role);
            }
        }

        User::query()
            ->find(1)
            ->assignRole('manager');
    }
}
