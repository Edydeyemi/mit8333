<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentStorageService
{
    /**
     * Store an evidence photo attached to a service request.
     */
    public function storeEvidence(
        UploadedFile $file,
        ServiceRequest $request,
        User $uploader
    ): ServiceRequestAttachment {
        $disk = $this->resolveDisk();
        $folder = 'evidence/' . date('Y/m');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::uuid()->toString() . '.' . $extension;

        $path = $file->storeAs($folder, $filename, $disk);

        return ServiceRequestAttachment::create([
            'service_request_id' => $request->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType() ?: 'image/jpeg',
            'file_size' => $file->getSize() ?: 0,
            'type' => 'evidence',
            'uploaded_by' => $uploader->id,
        ]);
    }

    /**
     * Resolve appropriate storage disk (S3 if credentials available, otherwise local public).
     */
    protected function resolveDisk(): string
    {
        $s3Key = config('filesystems.disks.s3.key');
        $s3Bucket = config('filesystems.disks.s3.bucket');

        if (class_exists(\Aws\S3\S3Client::class) && !empty($s3Key) && !empty($s3Bucket)) {
            return 's3';
        }

        return 'public';
    }

    /**
     * Delete an attachment from its storage disk.
     */
    public function delete(ServiceRequestAttachment $attachment): void
    {
        $disk = $this->resolveDisk();

        if (Storage::disk($disk)->exists($attachment->file_path)) {
            Storage::disk($disk)->delete($attachment->file_path);
        }

        // Also check fallback if disks swapped
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();
    }
}
