<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'company_id',
        'user_type',
        'allowed_ips',
        'permissions',
        'restrictions',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
        'active',
        'force_password_change',
        'password_changed_at',
        'metadata',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allowed_ips' => 'array',
            'permissions' => 'array',
            'restrictions' => 'array',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'active' => 'boolean',
            'force_password_change' => 'boolean',
            'password_changed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Relación con rol
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relación con empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function hasPermission(string $permission): bool
    {
        // Usuario inactivo no tiene permisos
        if (!$this->active) {
            return false;
        }

        // Verificar si está bloqueado
        if ($this->isLocked()) {
            return false;
        }

        // Super admin tiene todos los permisos
        if ($this->role && $this->role->name === 'super_admin') {
            return true;
        }

        // Verificar permisos específicos del usuario
        if ($this->permissions && in_array($permission, $this->permissions)) {
            return true;
        }

        // Verificar permisos del rol
        if ($this->role && $this->role->hasPermission($permission)) {
            return true;
        }

        // Verificar patrones comodín
        $allUserPermissions = $this->getAllPermissions();
        foreach ($allUserPermissions as $userPermission) {
            if (Permission::matchesPattern($permission, $userPermission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si el usuario tiene cualquiera de los permisos dados
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
     * Verificar si el usuario tiene todos los permisos dados
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
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Verificar si el usuario tiene cualquiera de los roles dados
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->role && in_array($this->role->name, $roles);
    }

    /**
     * Obtener todos los permisos del usuario (combinando usuario y rol)
     */
    public function getAllPermissions(): array
    {
        $userPermissions = $this->permissions ?? [];
        $rolePermissions = $this->role ? $this->role->getAllPermissions() : [];

        return array_unique(array_merge($userPermissions, $rolePermissions));
    }

    /**
     * Verificar si el usuario está bloqueado
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Verificar si el usuario puede acceder a una empresa
     */
    public function canAccessCompany(int $companyId): bool
    {
        // Super admin puede acceder a todas las empresas
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // Verificar si es la empresa asignada al usuario
        return $this->company_id === $companyId;
    }

    /**
     * Verificar si la IP está permitida para este usuario
     */
    public function isIpAllowed(string $ip): bool
    {
        // Si no hay restricciones de IP, está permitida
        if (!$this->allowed_ips) {
            return true;
        }

        // Verificar IP exacta
        if (in_array($ip, $this->allowed_ips)) {
            return true;
        }

        // Verificar rangos CIDR
        foreach ($this->allowed_ips as $allowedIp) {
            if (str_contains($allowedIp, '/')) {
                if ($this->ipInRange($ip, $allowedIp)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verificar si una IP está en un rango CIDR
     */
    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }

    /**
     * Registrar intento de login fallido
     */
    public function incrementFailedLoginAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        // Bloquear después de 5 intentos fallidos
        if ($this->failed_login_attempts >= 5) {
            $this->update([
                'locked_until' => now()->addMinutes(30)
            ]);
        }
    }

    /**
     * Resetear intentos de login fallido
     */
    public function resetFailedLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);
    }

    /**
     * Registrar login exitoso
     */
    public function recordSuccessfulLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByRole($query, string $roleName)
    {
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    public function scopeByUserType($query, string $userType)
    {
        return $query->where('user_type', $userType);
    }

    public function scopeNotLocked($query)
    {
        return $query->where(function($q) {
            $q->whereNull('locked_until')
              ->orWhere('locked_until', '<=', now());
        });
    }

    /**
     * Crear token API con permisos específicos
     */
    public function createApiToken(string $name, array $abilities = ['*']): \Laravel\Sanctum\NewAccessToken
    {
        // Filtrar abilities según permisos del usuario
        if (!in_array('*', $abilities)) {
            $abilities = array_filter($abilities, [$this, 'hasPermission']);
        }

        return $this->createToken($name, $abilities);
    }

    /**
     * Verificar si el usuario debe cambiar su contraseña
     */
    public function mustChangePassword(): bool
    {
        if ($this->force_password_change) {
            return true;
        }

        // Verificar si la contraseña es muy antigua (ej: más de 90 días)
        if ($this->password_changed_at) {
            return $this->password_changed_at->diffInDays(now()) > 90;
        }

        return false;
    }
}
