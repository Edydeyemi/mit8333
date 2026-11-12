<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Admin\Role;
use App\Traits\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile',
        'password',
        'role',
        'status',
        'profile_photo',
        'last_seen',
        'slug',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot model events to auto-generate slug.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user): void {
            if (empty($user->slug)) {
                $base = Str::slug("{$user->first_name}-{$user->last_name}");
                $user->slug = $base . '-' . Str::lower(Str::random(6));
            }
        });
    }

    /**
     * Get route key name for implicit model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Full Name Accessor.
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Full Name Alias Accessor.
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Initials Accessor.
     */
    public function getInitialsAttribute(): string
    {
        $f = !empty($this->first_name) ? mb_substr($this->first_name, 0, 1) : '';
        $l = !empty($this->last_name) ? mb_substr($this->last_name, 0, 1) : '';

        return mb_strtoupper($f . $l) ?: 'HW';
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is banned.
     */
    public function isBanned(): bool
    {
        return $this->status === 'banned';
    }

    /**
     * User OTPs relationship.
     */
    public function otps(): HasMany
    {
        return $this->hasMany(UserOtp::class);
    }

    /**
     * Roles relationship via pivot table.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    /**
     * Audit logs authored by this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    /**
     * Service requests lodged by this user.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'user_id');
    }

    /**
     * Service tasks assigned to this officer.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'assigned_officer_id');
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role || $this->roles()->where('role_slug', $role)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true) || $this->roles()->whereIn('role_slug', $roles)->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        if (!empty($this->role)) {
            $primaryRole = Role::where('role_slug', $this->role)->first();
            if ($primaryRole && $primaryRole->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }
}
