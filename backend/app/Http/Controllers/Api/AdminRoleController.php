<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminRoleController extends Controller
{
    /**
     * Get list of all roles with their permissions.
     */
    public function getRoles()
    {
        $roles = Role::with('permissions')->get();

        return response()->json($roles);
    }

    /**
     * Create a new role.
     */
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Clear Spatie Permission Cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->log("Membuat role baru: {$role->name}");

        return response()->json([
            'message' => "Role {$role->name} berhasil dibuat.",
            'role' => $role->load('permissions'),
        ], 201);
    }

    /**
     * Update permissions for a role (Role-Permission Matrix).
     */
    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Protect Super Admin from losing permissions or basic identity changes
        if ($role->name === 'super_admin') {
            return response()->json([
                'message' => 'Role Super Admin dilindungi dan tidak dapat diubah.',
            ], 403);
        }

        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name,'.$role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Clear Spatie Permission Cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->log("Memperbarui matriks permission untuk role: {$role->name}");

        return response()->json([
            'message' => "Role {$role->name} berhasil diperbarui.",
            'role' => $role->load('permissions'),
        ]);
    }

    /**
     * Delete a role.
     */
    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);

        // Protect Super Admin role from deletion (specified in plan.md)
        if ($role->name === 'super_admin') {
            return response()->json([
                'message' => 'Role Super Admin dilindungi dan tidak dapat dihapus.',
            ], 403);
        }

        $roleName = $role->name;
        $role->delete();

        // Clear Spatie Permission Cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->log("Menghapus role: {$roleName}");

        return response()->json([
            'message' => "Role {$roleName} berhasil dihapus.",
        ]);
    }

    /**
     * Get list of all permissions.
     */
    public function getPermissions()
    {
        $permissions = Permission::all();

        return response()->json($permissions);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        // If the user being modified is the only super admin, protect them
        if ($user->hasRole('super_admin') && ! in_array('super_admin', $request->roles)) {
            $superAdminsCount = User::role('super_admin')->count();
            if ($superAdminsCount <= 1) {
                return response()->json([
                    'message' => 'Gagal mengubah role. Harus ada minimal satu Super Admin di sistem.',
                ], 403);
            }
        }

        $user->syncRoles($request->roles);

        // Clear Spatie Permission Cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Log to Activity Log
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log("Mengubah role pengguna {$user->name} menjadi: ".implode(', ', $request->roles));

        return response()->json([
            'message' => "Role untuk pengguna {$user->name} berhasil diperbarui.",
            'user' => $user->load('roles'),
        ]);
    }
}
