<?php
namespace App\Listeners;

use App\Events\TransactionStatusUpdated;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendTransactionNotification
{
    protected $waService;

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }

    public function handle(TransactionStatusUpdated $event)
    {
        $transaction = $event->transaction;

        // Get the sender user via senderWallet relation
        $user = $transaction->senderWallet?->user;

        if (!$user) {
            Log::warning("Listener: No user found for transaction {$transaction->id}");
            return;
        }

        if (!$user->phone_number) {
            Log::warning("Listener: User {$user->id} has no phone number");
            return;
        }

        // Format phone for Twilio
        $phone = '+961' . preg_replace('/\D/', '', $user->phone_number);

        // Use the correct transaction ID field
        $transactionId = $transaction->id;

        // Prepare message
        $message = "Your transaction #{$transactionId} is now '{$transaction->status}'";

        Log::info("Listener: Sending WA to {$phone}: {$message}");

        // Send WhatsApp message
        $this->waService->sendWhatsAppMessage($phone, $message);

        // Store notification in DB
        $user->notifications()->create([
            'title' => 'Transaction Update',
            'message' => $message,
            'is_read' => false,
        ]);
    }
}