<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsDemoSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions para roles
        Permission::create(['guard_name' => 'api','name' => 'register_role']);
        Permission::create(['guard_name' => 'api','name' => 'list_role']);
        Permission::create(['guard_name' => 'api','name' => 'edit_role']);
        Permission::create(['guard_name' => 'api','name' => 'delete_role']);

        // create permissions para usuarios
        Permission::create(['guard_name' => 'api','name' => 'register_user']);
        Permission::create(['guard_name' => 'api','name' => 'list_user']);
        Permission::create(['guard_name' => 'api','name' => 'edit_user']);
        Permission::create(['guard_name' => 'api','name' => 'delete_user']);
        
    
        // ---------------------------------------------------------
        // CREATE ROLES AND ASSIGN EXISTING PERMISSIONS
        // ---------------------------------------------------------

        // Rol 1: Usuario Regular (Solo puede listar usuarios)
        $role1 = Role::create(['guard_name' => 'api','name' => 'User']);
        $role1->givePermissionTo('list_user');

        // Rol 2: Administrador (Puede gestionar usuarios, pero no roles)
        $role2 = Role::create(['guard_name' => 'api','name' => 'Admin']);
        $role2->givePermissionTo([
            'register_user',
            'list_user',
            'edit_user',
            'delete_user'
        ]);

        // Rol 3: Super-Admin (Tiene acceso a todo)
        $role3 = Role::create(['guard_name' => 'api','name' => 'Super-Admin']);
        // Nota: Generalmente el Super-Admin obtiene todos los permisos vía una regla Gate::before en AuthServiceProvider,
        // pero por si acaso, le asignamos todos explícitamente aquí para tu prueba:
        $role3->givePermissionTo(Permission::all());


        // ---------------------------------------------------------
        // CREATE TEST USERS AND ASSIGN ROLES
        // ---------------------------------------------------------

        // Usuario 1: Super-Admin (Tu usuario original)
        $superAdmin = \App\Models\User::factory()->create([
            'name' => 'Nolberto luis ',
            'email' => 'nolberto.sumaran@gmail.com',
            'password' => bcrypt('12345678')
        ]);
        $superAdmin->assignRole($role3);

        // Usuario 2: Admin
        $adminUser = \App\Models\User::factory()->create([
            'name' => 'Administrador de Prueba',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('12345678')
        ]);
        $adminUser->assignRole($role2);

        // Usuario 3: User normal
        $normalUser = \App\Models\User::factory()->create([
            'name' => 'Usuario de Prueba',
            'email' => 'user@gmail.com',
            'password' => bcrypt('12345678')
        ]);
        $normalUser->assignRole($role1);
    }
}