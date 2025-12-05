<?php

namespace App\Services;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    public function createNotification(User $user, string $title, string $message): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->user_id,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);

        // Real-time in-app broadcast
        broadcast(new NotificationSent($notification));

        // WhatsApp notification (if phone exists)
        if ($user->phone_number) {
            $this->whatsapp->sendWhatsAppMessage($user->phone_number, $message);
            Log::info("WhatsApp sent to: " . $user->phone_number);
        }

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
