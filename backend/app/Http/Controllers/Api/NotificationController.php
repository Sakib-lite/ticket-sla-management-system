<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()->notifications()->latest()->paginate(self::PER_PAGE);

        return NotificationResource::collection($notifications)->additional([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): NotificationResource
    {
        $found = $request->user()->notifications()->findOrFail($notification);
        $found->markAsRead();

        return NotificationResource::make($found);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
