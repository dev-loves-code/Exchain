<?php

namespace App\Services;

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

    public function sendToUser($userId, $title, $message)
    {
        $user = User::find($userId);

        if (!$user) return;

        Notification::create([
            'user_id' => $user->user_id,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);

        if ($user->phone_number) {
    $this->whatsapp->sendWhatsAppMessage($user->phone_number, $message);
        Log::error($user->phone_number);

        }
    }
}