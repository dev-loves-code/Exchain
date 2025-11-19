<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Notification $notification) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('user.'.$this->notification->user_id);
    }

    public function broadcastWith(): array
    {
        $data = [
            'notification_id' => $this->notification->notification_id,
            'title' => $this->notification->title,
            'message' => $this->notification->message,
            'is_read' => false,
            'created_at' => $this->notification->created_at->toISOString(),
        ];

        \Log::info('Broadcasting data:', $data);

        return $data;
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }
}
