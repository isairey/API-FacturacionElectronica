<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Inicializar sistema - Crear primer super admin
     */
    public function initialize(Request $request)
    {
        // Verificar si ya hay usuarios en el sistema
        if (User::count() > 0) {
            return response()->json([
                'message' => 'Sistema ya inicializado',
                'status' => 'error'
            ], 400);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        try {
            // Ejecutar seeder completo de roles y permisos automáticamente
            $this->runRolesAndPermissionsSeeder();

            // Obtener rol de super admin
            $superAdminRole = Role::where('name', 'super_admin')->first();

            // Crear primer usuario super admin
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $superAdminRole->id,
                'user_type' => 'system',
                'active' => true,
                'email_verified_at' => now(),
            ]);

            // Crear token de acceso
            $token = $user->createToken('API_INIT_TOKEN', ['*'])->plainTextToken;

            return response()->json([
                'message' => 'Sistema inicializado exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->display_name
                ],
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al inicializar sistema: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Login - Autenticación
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas',
                'status' => 'error'
            ], 401);
        }

        if (!$user->active) {
            return response()->json([
                'message' => 'Usuario inactivo',
                'status' => 'error'
            ], 401);
        }

        if ($user->isLocked()) {
            return response()->json([
                'message' => 'Usuario bloqueado',
                'status' => 'error'
            ], 401);
        }

        // Registrar login exitoso
        $user->recordSuccessfulLogin($request->ip());

        // Crear token
        $abilities = $user->role ? $user->role->getAllPermissions() : ['*'];
        $token = $user->createToken('API_ACCESS_TOKEN', $abilities)->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? $user->role->display_name : 'Sin rol',
                'company_id' => $user->company_id,
                'permissions' => $abilities
            ],
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Información del usuario autenticado
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('role', 'company');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? $user->role->display_name : 'Sin rol',
                'company' => $user->company ? $user->company->razon_social : null,
                'permissions' => $user->getAllPermissions(),
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at
            ]
        ]);
    }

    /**
     * Crear usuarios adicionales (solo super admin)
     */
    public function createUser(Request $request)
    {
        if (!$request->user()->hasRole('super_admin')) {
            return response()->json([
                'message' => 'No tienes permisos para crear usuarios',
                'status' => 'error'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Password::min(8)],
            'role_name' => 'required|string|exists:roles,name',
            'company_id' => 'nullable|integer|exists:companies,id',
            'user_type' => 'required|in:system,user,api_client',
        ]);

        try {
            $role = Role::where('name', $request->role_name)->first();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $role->id,
                'company_id' => $request->company_id,
                'user_type' => $request->user_type,
                'active' => true,
                'email_verified_at' => now(),
            ]);

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->display_name,
                    'user_type' => $user->user_type
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear usuario: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Ejecutar seeder completo de roles y permisos automáticamente
     */
    private function runRolesAndPermissionsSeeder()
    {
        // Instanciar el seeder y ejecutarlo directamente sin setCommand
        $seeder = new RolesAndPermissionsSeeder();
        
        // Ejecutar solo la creación de permisos y roles, no usuarios por defecto
        $seeder->runPermissionsAndRolesOnly();
    }

    /**
     * Obtener información del sistema
     */
    public function systemInfo()
    {
        $userCount = User::count();
        $isInitialized = $userCount > 0;

        return response()->json([
            'system_initialized' => $isInitialized,
            'user_count' => $userCount,
            'roles_count' => Role::count(),
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'database_connected' => $this->checkDatabaseConnection(),
        ]);
    }

    /**
     * Verificar conexión a la base de datos
     */
    private function checkDatabaseConnection(): bool
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}