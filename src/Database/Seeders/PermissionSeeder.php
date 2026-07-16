<?php

namespace Gingerminds\LaravelCms\Database\Seeders;

use Gingerminds\LaravelCore\Models\Permission\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::updateOrCreate(['name' => 'view menus', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'edit menus', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'delete menus', 'guard_name' => 'web']);

        Permission::updateOrCreate(['name' => 'view menu_items', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'edit menu_items', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'delete menu_items', 'guard_name' => 'web']);

        Permission::updateOrCreate(['name' => 'view pages', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'edit pages', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'delete pages', 'guard_name' => 'web']);

        Permission::updateOrCreate(['name' => 'view page_categories', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'edit page_categories', 'guard_name' => 'web']);
        Permission::updateOrCreate(['name' => 'delete page_categories', 'guard_name' => 'web']);

        $this->command->info('Permissions table seeded!');
        // updateOrCreate roles and assign existing permissions
    }
}
