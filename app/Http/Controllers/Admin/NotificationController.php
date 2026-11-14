<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a paginated listing of notifications with server-side filtering.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->input('status', 'all');

        $query = $user->notifications();

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $totalCount = $user->notifications()->count();
        $unreadCount = $user->unreadNotifications()->count();
        $readCount = $totalCount - $unreadCount;

        $filters = array_filter([
            'status' => ($status !== 'all') ? $status : null,
        ]);

        $notifications = $query->latest()
            ->paginate(15)
            ->appends($filters);

        return view('admin.notifications.notifications', compact(
            'notifications',
            'status',
            'totalCount',
            'unreadCount',
            'readCount'
        ));
    }

    /**
     * JSON Endpoint to fetch active unread notifications and badge counter.
     */
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'unread_count' => 0,
                'notifications' => [],
            ], 401);
        }

        $unreadCount = $user->unreadNotifications()->count();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($item): array {
                $data = is_array($item->data) ? $item->data : (json_decode($item->data, true) ?? []);

                return [
                    'id' => $item->id,
                    'title' => $data['title'] ?? 'System Dispatch Alert',
                    'message' => $data['message'] ?? 'Notification update received.',
                    'category' => $data['category'] ?? 'incident',
                    'url' => $data['url'] ?? null,
                    'icon' => $data['icon'] ?? 'bi-bell-fill',
                    'created_at' => $item->created_at?->toISOString(),
                    'time_ago' => $item->created_at ? Carbon::parse($item->created_at)->diffForHumans() : 'Just now',
                ];
            });

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
                'unread_count' => $user->unreadNotifications()->count(),
            ]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}

