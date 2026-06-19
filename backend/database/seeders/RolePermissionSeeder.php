<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // User & Verification management
            'verify_alumni',
            'suspend_alumni',
            'view_alumni_list',

            // Store management
            'apply_store',
            'verify_store',
            'manage_own_store',
            'suspend_store',

            // Category management
            'manage_categories',

            // Product & Service management
            'create_product',
            'update_product',
            'delete_product',
            'create_service',
            'update_service',
            'delete_service',

            // Order management
            'create_order', // checkout COD
            'update_order_status', // seller can update status
            'view_own_orders',
            'view_all_orders', // admin

            // Interaction
            'favorite_items',
            'create_review',
            'reply_review',

            // Reports & Log
            'view_reports',
            'view_activity_logs',
        ];

        // Create permissions using create() for database-level insert
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Force reload permissions list in Spatie Permission package
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles and assign existing permissions

        // 1. Alumni Pembeli (Default role upon registration)
        $rolePembeli = Role::create(['name' => 'alumni_pembeli', 'guard_name' => 'web']);
        $rolePembeli->givePermissionTo([
            'apply_store',
            'create_order',
            'view_own_orders',
            'favorite_items',
            'create_review',
        ]);

        // 2. Alumni Penjual
        $rolePenjual = Role::create(['name' => 'alumni_penjual', 'guard_name' => 'web']);
        $rolePenjual->givePermissionTo([
            'apply_store',
            'manage_own_store',
            'create_product',
            'update_product',
            'delete_product',
            'create_service',
            'update_service',
            'delete_service',
            'update_order_status',
            'view_own_orders',
            'favorite_items',
            'create_review',
            'reply_review',
        ]);

        // 3. Admin Marketplace
        $roleAdminMarketplace = Role::create(['name' => 'admin_marketplace', 'guard_name' => 'web']);
        $roleAdminMarketplace->givePermissionTo([
            'verify_alumni',
            'suspend_alumni',
            'view_alumni_list',
            'verify_store',
            'suspend_store',
            'manage_categories',
            'view_all_orders',
            'view_reports',
            'view_activity_logs',
        ]);

        // 4. Super Admin
        $roleSuperAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }
}
