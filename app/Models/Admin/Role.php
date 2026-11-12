<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, Auditable;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'role_slug',
        'created_by',
        'updated_by',
    ];

    /**
     * Get route key name for implicit model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'role_slug';
    }

    /**
     * Get the permissions associated with the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withPivot('created_by', 'updated_by')
            ->withTimestamps();
    }

    /**
     * Get the users assigned to this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    /**
     * Check if role has a given permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }
}
