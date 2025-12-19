<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $query = Notification::where('user_id', $user->id);

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by read status
            if ($request->has('is_read')) {
                $query->where('is_read', $request->is_read);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Count unread notifications
            $unreadCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return ApiResponse::success([
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ], 'Notifications fetched successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch notifications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::where('user_id', $user->id)
                ->findOrFail($id);

            $notification->is_read = true;
            $notification->save();

            return ApiResponse::success($notification, 'Notification marked as read');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to mark notification as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();

            $updated = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return ApiResponse::success([
                'updated_count' => $updated,
            ], 'All notifications marked as read');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to mark all as read: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();

            $notification = Notification::where('user_id', $user->id)
                ->findOrFail($id);

            $notification->delete();

            return ApiResponse::success(null, 'Notification deleted successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete notification: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get unread notification count
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();

            $count = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return ApiResponse::success([
                'unread_count' => $count,
            ], 'Unread count fetched');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch unread count: ' . $e->getMessage(), 500);
        }
    }
}
