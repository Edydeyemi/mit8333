<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequestStatusUpdate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_request_status_updates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_request_id',
        'user_id',
        'previous_status',
        'status',
        'notes',
    ];

    /**
     * Associated service request relationship.
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * User / Officer who created this status update.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Attachments uploaded with this specific status update.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ServiceRequestAttachment::class, 'status_update_id');
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
}

