<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function viewAny()
    {
        $roles = Role::query()
            ->with('permissions:id')
            ->get()
            ->map(fn ($role) => [
                ...$role->toArray(),
                'permissions' => $role->permissions->pluck('id'),
            ]);

        $permissions = Permission::query()
            ->select('id', 'name')
            ->get();

        return [
            'roles' => $roles,
            'permissions' => $permissions,
        ];
    }

    public function syncRoles(Request $request)
    {
        $roles = Role::query()
            ->get();

        foreach ($roles as $role) {
            $permissions = $request->roles[$role->id] ?? [];

            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.viewAny');
    }
}
