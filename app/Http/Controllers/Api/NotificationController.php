<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    // GET /notifications — returns all notifications for the logged-in user
    public function index(Request $request)
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'type'       => $n->type,
                    'title'      => $n->title,
                    'body'       => $n->body,
                    'data'       => $n->data,
                    'is_read'    => $n->read_at !== null,
                    'created_at' => $n->created_at?->toIso8601String(),
                ];
            });

        $unreadCount = AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return $this->successResponse([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ], 'Notifications retrieved');
    }

    // POST /notifications/{id}/read
    public function markRead(Request $request, $id)
    {
        $notif = AppNotification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notif->update(['read_at' => now()]);
        return $this->successResponse(null, 'Marked as read');
    }

    // POST /notifications/read-all
    public function markAllRead(Request $request)
    {
        AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->successResponse(null, 'All marked as read');
    }
}
