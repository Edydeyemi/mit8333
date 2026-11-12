<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the Auditable trait.
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model): void {
            self::recordAuditTrail($model, 'created');
        });

        static::updated(function ($model): void {
            if ($model->wasChanged()) {
                self::recordAuditTrail($model, 'updated');
            }
        });
    }

    /**
     * Record an audit log entry.
     */
    protected static function recordAuditTrail($model, string $action): void
    {
        $actor = Auth::user();
        $actorId = $actor?->id;
        $actorName = $actor ? trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? '')) : 'system';

        $excluded = [
            'password',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        $changes = null;

        if ($action === 'updated') {
            $rawChanges = $model->getChanges();
            $filteredChanges = [];
            foreach ($rawChanges as $key => $newValue) {
                if (in_array($key, $excluded, true)) {
                    continue;
                }
                $filteredChanges[$key] = [
                    'old' => $model->getOriginal($key),
                    'new' => $newValue,
                ];
            }

            if (empty($filteredChanges)) {
                return;
            }

            $changes = $filteredChanges;
        } elseif ($action === 'created') {
            $rawAttributes = $model->getAttributes();
            $filteredAttributes = [];
            foreach ($rawAttributes as $key => $value) {
                if (!in_array($key, $excluded, true)) {
                    $filteredAttributes[$key] = $value;
                }
            }
            $changes = ['attributes' => $filteredAttributes];
        }

        $request = request();

        AuditLog::create([
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'actor_id' => $actorId,
            'actor_name' => $actorName ?: 'system',
            'changes' => $changes,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}

