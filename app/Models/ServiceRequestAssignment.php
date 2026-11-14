<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ServiceRequestAssignment extends Model
{
    use HasFactory, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_request_assignments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'service_request_id',
        'officer_id',
        'assigned_by_id',
        'assigned_at',
        'unassigned_at',
        'status',
        'notes',
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
            'unassigned_at' => 'datetime',
        ];
    }

    /**
     * Boot model events for automatic slug generation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ServiceRequestAssignment $assignment): void {
            if (empty($assignment->slug)) {
                $assignment->slug = (string) Str::uuid();
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
     * Service request relationship.
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * Assigned maintenance officer relationship.
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    /**
     * Administrator who dispatched the assignment.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
