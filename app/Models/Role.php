<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_system',
        'active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Relación con permisos (muchos a muchos)
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Relación con usuarios
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Verificar si el rol tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        // Verificar en permisos rápidos (JSON)
        if ($this->permissions && in_array($permission, $this->permissions)) {
            return true;
        }

        // Verificar en relación many-to-many
        return $this->permissions()->where('name', $permission)->where('active', true)->exists();
    }

    /**
     * Verificar si el rol tiene cualquiera de los permisos dados
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si el rol tiene todos los permisos dados
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Asignar permiso al rol
     */
    public function givePermission(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->permissions()->where('permission_id', $permission->id)->exists()) {
            $this->permissions()->attach($permission);
        }

        return $this;
    }

    /**
     * Revocar permiso del rol
     */
    public function revokePermission(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission);

        return $this;
    }

    /**
     * Sincronizar permisos del rol
     */
    public function syncPermissions(array $permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if (is_string($permission)) {
                return Permission::where('name', $permission)->firstOrFail()->id;
            }
            return $permission instanceof Permission ? $permission->id : $permission;
        })->toArray();

        $this->permissions()->sync($permissionIds);

        return $this;
    }

    /**
     * Obtener todos los permisos del rol (combinando JSON y relación)
     */
    public function getAllPermissions(): array
    {
        $jsonPermissions = $this->permissions ?? [];
        $relationPermissions = $this->permissions()->where('active', true)->pluck('name')->toArray();

        return array_unique(array_merge($jsonPermissions, $relationPermissions));
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Roles predefinidos del sistema
     */
    public static function getSystemRoles(): array
    {
        return [
            'super_admin' => [
                'display_name' => 'Super Administrador',
                'description' => 'Control total del sistema, todas las empresas y usuarios',
                'permissions' => ['*'], // Todos los permisos
                'is_system' => true,
            ],
            'company_admin' => [
                'display_name' => 'Administrador de Empresa',
                'description' => 'Administra completamente una empresa específica',
                'permissions' => [
                    'company.manage',
                    'invoices.*',
                    'boletas.*',
                    'credit_notes.*',
                    'debit_notes.*',
                    'dispatch_guides.*',
                    'daily_summaries.*',
                    'users.manage',
                ],
                'is_system' => true,
            ],
            'company_user' => [
                'display_name' => 'Usuario de Empresa',
                'description' => 'Puede crear y gestionar documentos de su empresa',
                'permissions' => [
                    'invoices.create',
                    'invoices.view',
                    'invoices.send',
                    'boletas.create',
                    'boletas.view',
                    'boletas.send',
                    'credit_notes.create',
                    'credit_notes.view',
                    'debit_notes.create',
                    'debit_notes.view',
                    'dispatch_guides.create',
                    'dispatch_guides.view',
                ],
                'is_system' => true,
            ],
            'api_client' => [
                'display_name' => 'Cliente API',
                'description' => 'Acceso API externo con permisos limitados',
                'permissions' => [
                    'api.access',
                    'invoices.create',
                    'invoices.view',
                    'boletas.create',
                    'boletas.view',
                ],
                'is_system' => true,
            ],
            'read_only' => [
                'display_name' => 'Solo Lectura',
                'description' => 'Solo puede consultar documentos y reportes',
                'permissions' => [
                    'invoices.view',
                    'boletas.view',
                    'credit_notes.view',
                    'debit_notes.view',
                    'dispatch_guides.view',
                    'reports.view',
                ],
                'is_system' => true,
            ],
        ];
    }

    /**
     * Verificar si es un rol de sistema crítico
     */
    public function isCriticalSystemRole(): bool
    {
        return $this->is_system && in_array($this->name, ['super_admin', 'company_admin']);
    }
}