<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Upload and update user profile avatar.
     */
    public function updateAvatar(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120', // 5MB
            ],
        ], [
            'avatar.required' => 'Please select an image file to upload.',
            'avatar.image' => 'The selected file must be an image.',
            'avatar.mimes' => 'Permitted image formats are JPEG, PNG, JPG, or WEBP.',
            'avatar.max' => 'The avatar file size must not exceed 5MB.',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $file = $request->file('avatar');

        // Delete previous avatar file if exists locally
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::uuid()->toString() . '.' . $extension;
        $path = $file->storeAs('avatars', $filename, 'public');

        $user->profile_photo = $path;
        $user->save();

        AuditLog::log('profile_avatar_updated', $user, ['profile_photo' => $path], $user);

        $successMsg = 'Profile picture updated successfully.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMsg,
                'avatar_url' => $user->profile_photo_url,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Remove existing profile avatar.
     */
    public function removeAvatar(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->profile_photo = null;
        $user->save();

        AuditLog::log('profile_avatar_removed', $user, null, $user);

        $successMsg = 'Profile picture removed.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMsg,
                'avatar_url' => null,
            ]);
        }

        return back()->with('success', $successMsg);
    }
}

