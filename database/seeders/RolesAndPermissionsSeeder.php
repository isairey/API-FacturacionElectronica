<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createPermissions();
        $this->createRoles();
        $this->createDefaultUsers();
    }

    /**
     * Ejecutar solo la creación de permisos y roles (sin usuarios)
     * Usado desde AuthController durante la inicialización
     */
    public function runPermissionsAndRolesOnly(): void
    {
        $this->createPermissions();
        $this->createRoles();
    }

    /**
     * Crear permisos del sistema
     */
    private function createPermissions(): void
    {
        $this->logInfo('Creando permisos del sistema...');

        $permissions = Permission::getSystemPermissions();

        foreach ($permissions as $category => $categoryPermissions) {
            foreach ($categoryPermissions as $name => $data) {
                Permission::updateOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $data['display_name'],
                        'description' => $data['description'],
                        'category' => $category,
                        'is_system' => true,
                        'active' => true,
                    ]
                );
            }
        }

        $this->logInfo('Permisos creados: ' . Permission::count());
    }

    /**
     * Crear roles del sistema
     */
    private function createRoles(): void
    {
        $this->logInfo('Creando roles del sistema...');

        $roles = Role::getSystemRoles();

        foreach ($roles as $name => $data) {
            $role = Role::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $data['display_name'],
                    'description' => $data['description'],
                    'permissions' => $data['permissions'],
                    'is_system' => $data['is_system'],
                    'active' => true,
                ]
            );

            // Asignar permisos específicos al rol (no comodín)
            $this->assignPermissionsToRole($role, $data['permissions']);
        }

        $this->logInfo('Roles creados: ' . Role::count());
    }

    /**
     * Asignar permisos específicos a un rol
     */
    private function assignPermissionsToRole(Role $role, array $permissions): void
    {
        if (in_array('*', $permissions)) {
            // Si tiene permiso global, asignar todos los permisos
            $allPermissions = Permission::active()->get();
            $role->permissions()->sync($allPermissions->pluck('id'));
            return;
        }

        $permissionIds = [];

        foreach ($permissions as $permission) {
            if (str_contains($permission, '*')) {
                // Expandir permisos comodín
                $expandedPermissions = Permission::expandWildcardPermission($permission);
                
                foreach ($expandedPermissions as $expandedPermission) {
                    $permissionModel = Permission::where('name', $expandedPermission)->first();
                    if ($permissionModel) {
                        $permissionIds[] = $permissionModel->id;
                    }
                }
            } else {
                // Permiso específico
                $permissionModel = Permission::where('name', $permission)->first();
                if ($permissionModel) {
                    $permissionIds[] = $permissionModel->id;
                }
            }
        }

        $role->permissions()->sync(array_unique($permissionIds));
    }

    /**
     * Crear usuarios por defecto
     */
    private function createDefaultUsers(): void
    {
        $this->logInfo('Creando usuarios por defecto...');

        // Super Admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        
        User::updateOrCreate(
            ['email' => 'admin@sunatapi.com'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('admin123456'),
                'role_id' => $superAdminRole->id,
                'user_type' => 'system',
                'active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Usuario de prueba para company admin
        $companyAdminRole = Role::where('name', 'company_admin')->first();
        
        User::updateOrCreate(
            ['email' => 'company@sunatapi.com'],
            [
                'name' => 'Administrador de Empresa',
                'password' => Hash::make('company123456'),
                'role_id' => $companyAdminRole->id,
                'company_id' => 1, // Asumiendo que existe empresa con ID 1
                'user_type' => 'user',
                'active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Usuario API de prueba
        $apiClientRole = Role::where('name', 'api_client')->first();
        
        $apiUser = User::updateOrCreate(
            ['email' => 'api@sunatapi.com'],
            [
                'name' => 'Cliente API',
                'password' => Hash::make('api123456'),
                'role_id' => $apiClientRole->id,
                'company_id' => 1,
                'user_type' => 'api_client',
                'active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Crear token API de prueba
        $token = $apiUser->createApiToken('API Token', [
            'invoices.create',
            'invoices.view',
            'boletas.create',
            'boletas.view',
        ]);

        $this->logInfo('Usuarios creados exitosamente');
        $this->logWarning('CREDENCIALES DE ACCESO:');
        $this->logInfo('Super Admin: admin@sunatapi.com / admin123456');
        $this->logInfo('Company Admin: company@sunatapi.com / company123456');
        $this->logInfo('API Client: api@sunatapi.com / api123456');
        $this->logWarning('TOKEN API DE PRUEBA:');
        $this->logInfo($token->plainTextToken);
        $this->logError('⚠️  CAMBIAR ESTAS CREDENCIALES EN PRODUCCIÓN ⚠️');
    }

    /**
     * Métodos de logging que funcionan tanto desde consola como desde controlador
     */
    private function logInfo($message): void
    {
        if ($this->command) {
            $this->command->info($message);
        } else {
            \Log::info("RolesSeeder: $message");
        }
    }

    private function logWarning($message): void
    {
        if ($this->command) {
            $this->command->warn($message);
        } else {
            \Log::warning("RolesSeeder: $message");
        }
    }

    private function logError($message): void
    {
        if ($this->command) {
            $this->command->error($message);
        } else {
            \Log::error("RolesSeeder: $message");
        }
    }
}
