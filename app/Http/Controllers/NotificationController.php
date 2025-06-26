<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\UserNotificationSetting;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class NotificationController extends Controller
{
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = $user->notificationSettings()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'order_updates_enabled' => true,
                'new_products_enabled' => true
            ]
        );

        return response()->json([
            'status' => true,
            'data' => [
                [
                    'id' => 1,
                    'title' => 'Order Updates',
                    'description' => 'Get notified about your order status',
                    'enabled' => (bool)$settings->order_updates_enabled
                ],
                [
                    'id' => 2,
                    'title' => 'New Products',
                    'description' => 'Be the first to know about new arrivals',
                    'enabled' => (bool)$settings->new_products_enabled
                ]
            ]
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notificationSettings' => 'required|array',
            'notificationSettings.*.id' => 'required|integer|in:1,2',
            'notificationSettings.*.enabled' => 'required|boolean'
        ]);

        $settings = $request->user()->notificationSettings()->firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'order_updates_enabled' => true,
                'new_products_enabled' => true
            ]
        );

        foreach ($validated['notificationSettings'] as $setting) {
            switch ($setting['id']) {
                case 1:
                    $settings->order_updates_enabled = $setting['enabled'];
                    break;
                case 2:
                    $settings->new_products_enabled = $setting['enabled'];
                    break;
            }
        }

        $settings->save();

        return response()->json([
            'status' => true,
            'message' => 'Notification settings updated successfully'
        ]);
    }

    public function markAsRead($id): JsonResponse
    {
        $request->validate([
            'id' => 'required|exists:notifications,id,user_id,'.Auth::id()
        ]);

        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->update([
            'read' => true,
            'read_at' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    public function getUnreadCount(): JsonResponse
    {
        $count = Auth::user()->notifications()
                    ->where('read', false)
                    ->count();

        return response()->json([
            'status' => true,
            'count' => $count
        ]);
    }
}
