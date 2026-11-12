<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class ServiceRequest extends Model
{
    use HasFactory, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'tracking_code',
        'user_id',
        'service_category_id',
        'title',
        'description',
        'priority',
        'status',
        'building',
        'wing_floor',
        'room_number',
        'assigned_officer_id',
        'assigned_at',
        'resolved_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Boot model events for automatic slug and tracking code generation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ServiceRequest $request): void {
            if (empty($request->slug)) {
                $request->slug = (string) Str::uuid();
            }

            if (empty($request->tracking_code)) {
                $year = date('Y');
                do {
                    $code = 'VRTS-' . $year . '-' . strtoupper(Str::random(5));
                } while (static::where('tracking_code', $code)->exists());

                $request->tracking_code = $code;
            }
        });
    }

    /**
     * Route key name for implicit model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Requester user relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Service category relationship.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * Assigned maintenance officer relationship.
     */
    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    /**
     * All attachments relationship.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ServiceRequestAttachment::class, 'service_request_id');
    }

    /**
     * Evidence photos attached by requester.
     */
    public function evidencePhotos(): HasMany
    {
        return $this->hasMany(ServiceRequestAttachment::class, 'service_request_id')
            ->where('type', 'evidence');
    }

    /**
     * Resolution proof attachments.
     */
    public function resolutionProofs(): HasMany
    {
        return $this->hasMany(ServiceRequestAttachment::class, 'service_request_id')
            ->where('type', 'resolution_proof');
    }

    /**
     * All status progression updates history.
     */
    public function statusUpdates(): HasMany
    {
        return $this->hasMany(ServiceRequestStatusUpdate::class, 'service_request_id')->latest();
    }

    /**
     * Audit logs relationship.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }

    /**
     * Determine if a user can cancel this request.
     */
    public function canBeCancelledBy(User $user): bool
    {
        return $this->user_id === $user->id && in_array($this->status, ['submitted', 'assigned'], true);
    }

    /**
     * Priority badge CSS class accessor.
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'emergency' => 'badge-priority priority-emergency',
            'high' => 'badge-priority priority-high',
            'medium' => 'badge-priority priority-medium',
            'low' => 'badge-priority priority-low',
            default => 'badge-priority priority-medium',
        };
    }

    /**
     * Status badge CSS class accessor.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'badge-status badge-submitted',
            'assigned' => 'badge-status badge-assigned',
            'in_progress' => 'badge-status badge-in_progress',
            'on_hold' => 'badge-status badge-on_hold',
            'resolved' => 'badge-status badge-resolved',
            'cancelled' => 'badge-status badge-cancelled',
            'rejected' => 'badge-status badge-rejected',
            default => 'badge-status badge-submitted',
        };
    }

    /**
     * Formatted human-readable status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'Submitted',
            'assigned' => 'Officer Assigned',
            'in_progress' => 'In Progress',
            'on_hold' => 'On Hold',
            'resolved' => 'Resolved',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Scope query to requests lodged by a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to requests assigned to a specific facility officer.
     */
    public function scopeForOfficer(Builder $query, int $officerId): Builder
    {
        return $query->where('assigned_officer_id', $officerId);
    }

    /**
     * Scope query to filter by search query, category, and status.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function (Builder $q) use ($search): void {
                $q->where('tracking_code', 'LIKE', $search)
                    ->orWhere('title', 'LIKE', $search)
                    ->orWhere('building', 'LIKE', $search)
                    ->orWhere('room_number', 'LIKE', $search)
                    ->orWhere('wing_floor', 'LIKE', $search);
            });
        }

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('service_category_id', (int) $filters['category']);
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['building']) && $filters['building'] !== 'all') {
            $query->where('building', $filters['building']);
        }

        return $query;
    }
}
