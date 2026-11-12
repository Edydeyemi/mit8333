<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ServiceRequestAttachment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_request_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_request_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'type',
        'uploaded_by',
    ];

    /**
     * Associated service request relationship.
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    /**
     * User who uploaded the attachment.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get accessible URL for the attachment (S3 signed URL or local storage URL).
     */
    public function getUrlAttribute(): string
    {
        $s3Key = config('filesystems.disks.s3.key');
        $s3Bucket = config('filesystems.disks.s3.bucket');

        if (class_exists(\Aws\S3\S3Client::class) && !empty($s3Key) && !empty($s3Bucket)) {
            try {
                if (Storage::disk('s3')->exists($this->file_path)) {
                    return Storage::disk('s3')->temporaryUrl($this->file_path, now()->addMinutes(60));
                }
            } catch (\Throwable) {
                // Fallback if S3 temporary URL fails
            }
        }

        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Format file size for human display.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Determine if file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }
}
