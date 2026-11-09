<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'action',
        'actor_id',
        'actor_name',
        'changes',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * Get the owning auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Actor (User) who performed the action.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Log a custom action.
     */
    public static function log(
        string $action,
        ?Model $model = null,
        ?array $changes = null,
        ?User $actor = null
    ): self {
        $request = request();
        $currentUser = $actor ?? ($request ? $request->user() : null);

        return self::create([
            'auditable_type' => $model ? get_class($model) : User::class,
            'auditable_id' => $model?->getKey(),
            'action' => $action,
            'actor_id' => $currentUser?->id,
            'actor_name' => $currentUser ? trim(($currentUser->first_name ?? '') . ' ' . ($currentUser->last_name ?? '')) : 'system',
            'changes' => $changes,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}

