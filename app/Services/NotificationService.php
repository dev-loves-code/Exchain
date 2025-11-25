<?php

namespace App\Services;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function createNotification(User $user, string $title, string $message): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->user_id,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);

        broadcast(new NotificationSent($notification));

        return $notification;
    }

    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->where('is_read', false)->count();
    }

    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = Notification::where('notification_id', $notificationId)
            ->where('user_id', $user->user_id)
            ->first();

        if ($notification) {
            $notification->markAsRead();

            return true;
        }

        return false;
    }
}
