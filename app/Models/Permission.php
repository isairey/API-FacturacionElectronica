<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
        'is_system',
        'active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Relación con roles (muchos a muchos)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
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

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Permisos predefinidos del sistema
     */
    public static function getSystemPermissions(): array
    {
        return [
            // Sistema general
            'system' => [
                'system.manage' => ['display_name' => 'Administrar Sistema', 'description' => 'Acceso completo al sistema'],
                'system.config' => ['display_name' => 'Configurar Sistema', 'description' => 'Configurar parámetros del sistema'],
                'system.logs' => ['display_name' => 'Ver Logs', 'description' => 'Acceder a logs del sistema'],
                'api.access' => ['display_name' => 'Acceso API', 'description' => 'Acceso básico a la API'],
            ],

            // Empresas
            'companies' => [
                'companies.view' => ['display_name' => 'Ver Empresas', 'description' => 'Ver información de empresas'],
                'companies.create' => ['display_name' => 'Crear Empresas', 'description' => 'Crear nuevas empresas'],
                'companies.update' => ['display_name' => 'Editar Empresas', 'description' => 'Editar información de empresas'],
                'companies.delete' => ['display_name' => 'Eliminar Empresas', 'description' => 'Eliminar empresas'],
                'companies.manage' => ['display_name' => 'Administrar Empresa', 'description' => 'Administrar completamente una empresa'],
                'companies.config' => ['display_name' => 'Configurar Empresa', 'description' => 'Configurar parámetros de empresa'],
            ],

            // Usuarios
            'users' => [
                'users.view' => ['display_name' => 'Ver Usuarios', 'description' => 'Ver usuarios del sistema'],
                'users.create' => ['display_name' => 'Crear Usuarios', 'description' => 'Crear nuevos usuarios'],
                'users.update' => ['display_name' => 'Editar Usuarios', 'description' => 'Editar información de usuarios'],
                'users.delete' => ['display_name' => 'Eliminar Usuarios', 'description' => 'Eliminar usuarios'],
                'users.manage' => ['display_name' => 'Administrar Usuarios', 'description' => 'Administrar usuarios de la empresa'],
                'users.roles' => ['display_name' => 'Asignar Roles', 'description' => 'Asignar y modificar roles'],
            ],

            // Facturas
            'invoices' => [
                'invoices.view' => ['display_name' => 'Ver Facturas', 'description' => 'Ver facturas'],
                'invoices.create' => ['display_name' => 'Crear Facturas', 'description' => 'Crear nuevas facturas'],
                'invoices.update' => ['display_name' => 'Editar Facturas', 'description' => 'Editar facturas existentes'],
                'invoices.delete' => ['display_name' => 'Eliminar Facturas', 'description' => 'Eliminar facturas'],
                'invoices.send' => ['display_name' => 'Enviar Facturas', 'description' => 'Enviar facturas a SUNAT'],
                'invoices.download' => ['display_name' => 'Descargar Facturas', 'description' => 'Descargar XML/PDF/CDR'],
            ],

            // Boletas
            'boletas' => [
                'boletas.view' => ['display_name' => 'Ver Boletas', 'description' => 'Ver boletas'],
                'boletas.create' => ['display_name' => 'Crear Boletas', 'description' => 'Crear nuevas boletas'],
                'boletas.update' => ['display_name' => 'Editar Boletas', 'description' => 'Editar boletas existentes'],
                'boletas.delete' => ['display_name' => 'Eliminar Boletas', 'description' => 'Eliminar boletas'],
                'boletas.send' => ['display_name' => 'Enviar Boletas', 'description' => 'Enviar boletas a SUNAT'],
                'boletas.download' => ['display_name' => 'Descargar Boletas', 'description' => 'Descargar XML/PDF/CDR'],
            ],

            // Notas de Crédito
            'credit_notes' => [
                'credit_notes.view' => ['display_name' => 'Ver Notas de Crédito', 'description' => 'Ver notas de crédito'],
                'credit_notes.create' => ['display_name' => 'Crear Notas de Crédito', 'description' => 'Crear notas de crédito'],
                'credit_notes.update' => ['display_name' => 'Editar Notas de Crédito', 'description' => 'Editar notas de crédito'],
                'credit_notes.delete' => ['display_name' => 'Eliminar Notas de Crédito', 'description' => 'Eliminar notas de crédito'],
                'credit_notes.send' => ['display_name' => 'Enviar Notas de Crédito', 'description' => 'Enviar notas de crédito a SUNAT'],
                'credit_notes.download' => ['display_name' => 'Descargar Notas de Crédito', 'description' => 'Descargar archivos'],
            ],

            // Notas de Débito
            'debit_notes' => [
                'debit_notes.view' => ['display_name' => 'Ver Notas de Débito', 'description' => 'Ver notas de débito'],
                'debit_notes.create' => ['display_name' => 'Crear Notas de Débito', 'description' => 'Crear notas de débito'],
                'debit_notes.update' => ['display_name' => 'Editar Notas de Débito', 'description' => 'Editar notas de débito'],
                'debit_notes.delete' => ['display_name' => 'Eliminar Notas de Débito', 'description' => 'Eliminar notas de débito'],
                'debit_notes.send' => ['display_name' => 'Enviar Notas de Débito', 'description' => 'Enviar notas de débito a SUNAT'],
                'debit_notes.download' => ['display_name' => 'Descargar Notas de Débito', 'description' => 'Descargar archivos'],
            ],

            // Guías de Remisión
            'dispatch_guides' => [
                'dispatch_guides.view' => ['display_name' => 'Ver Guías de Remisión', 'description' => 'Ver guías de remisión'],
                'dispatch_guides.create' => ['display_name' => 'Crear Guías de Remisión', 'description' => 'Crear guías de remisión'],
                'dispatch_guides.update' => ['display_name' => 'Editar Guías de Remisión', 'description' => 'Editar guías de remisión'],
                'dispatch_guides.delete' => ['display_name' => 'Eliminar Guías de Remisión', 'description' => 'Eliminar guías de remisión'],
                'dispatch_guides.send' => ['display_name' => 'Enviar Guías de Remisión', 'description' => 'Enviar guías a SUNAT'],
                'dispatch_guides.check' => ['display_name' => 'Consultar Estado GRE', 'description' => 'Consultar estado en SUNAT'],
                'dispatch_guides.download' => ['display_name' => 'Descargar Guías', 'description' => 'Descargar archivos'],
            ],

            // Resúmenes Diarios
            'daily_summaries' => [
                'daily_summaries.view' => ['display_name' => 'Ver Resúmenes Diarios', 'description' => 'Ver resúmenes diarios'],
                'daily_summaries.create' => ['display_name' => 'Crear Resúmenes Diarios', 'description' => 'Crear resúmenes diarios'],
                'daily_summaries.send' => ['display_name' => 'Enviar Resúmenes', 'description' => 'Enviar resúmenes a SUNAT'],
                'daily_summaries.check' => ['display_name' => 'Consultar Estado', 'description' => 'Consultar estado en SUNAT'],
                'daily_summaries.download' => ['display_name' => 'Descargar Resúmenes', 'description' => 'Descargar archivos'],
            ],

            // Comunicaciones de Baja
            'voided_documents' => [
                'voided_documents.view' => ['display_name' => 'Ver Comunicaciones de Baja', 'description' => 'Ver comunicaciones de baja'],
                'voided_documents.create' => ['display_name' => 'Crear Comunicaciones de Baja', 'description' => 'Crear comunicaciones de baja'],
                'voided_documents.send' => ['display_name' => 'Enviar Comunicaciones', 'description' => 'Enviar comunicaciones a SUNAT'],
                'voided_documents.check' => ['display_name' => 'Consultar Estado', 'description' => 'Consultar estado en SUNAT'],
                'voided_documents.download' => ['display_name' => 'Descargar Comunicaciones', 'description' => 'Descargar archivos'],
            ],

            // Reportes
            'reports' => [
                'reports.view' => ['display_name' => 'Ver Reportes', 'description' => 'Ver reportes del sistema'],
                'reports.export' => ['display_name' => 'Exportar Reportes', 'description' => 'Exportar reportes en diferentes formatos'],
            ],

            // Configuraciones
            'config' => [
                'config.view' => ['display_name' => 'Ver Configuraciones', 'description' => 'Ver configuraciones'],
                'config.update' => ['display_name' => 'Editar Configuraciones', 'description' => 'Editar configuraciones'],
            ],
        ];
    }

    /**
     * Obtener permisos por categoría
     */
    public static function getPermissionsByCategory(string $category): array
    {
        $permissions = self::getSystemPermissions();
        return $permissions[$category] ?? [];
    }

    /**
     * Obtener todas las categorías de permisos
     */
    public static function getCategories(): array
    {
        return array_keys(self::getSystemPermissions());
    }

    /**
     * Verificar si un permiso existe en el sistema
     */
    public static function permissionExists(string $permission): bool
    {
        $allPermissions = collect(self::getSystemPermissions())->flatten(1);
        return $allPermissions->has($permission);
    }

    /**
     * Verificar si es un permiso comodín (wildcard)
     */
    public static function isWildcardPermission(string $permission): bool
    {
        return str_contains($permission, '*');
    }

    /**
     * Expandir permisos comodín
     */
    public static function expandWildcardPermission(string $permission): array
    {
        if (!self::isWildcardPermission($permission)) {
            return [$permission];
        }

        if ($permission === '*') {
            // Devolver todos los permisos
            return collect(self::getSystemPermissions())
                ->flatten(1)
                ->keys()
                ->toArray();
        }

        // Expandir categoría específica (ej: invoices.*)
        $category = str_replace('.*', '', $permission);
        return array_keys(self::getPermissionsByCategory($category));
    }

    /**
     * Verificar si un permiso coincide con un patrón
     */
    public static function matchesPattern(string $permission, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        if (!str_contains($pattern, '*')) {
            return $permission === $pattern;
        }

        // Convertir patrón a regex
        $regex = '/^' . str_replace(['*', '.'], ['.*', '\.'], $pattern) . '$/';
        return preg_match($regex, $permission) === 1;
    }
}