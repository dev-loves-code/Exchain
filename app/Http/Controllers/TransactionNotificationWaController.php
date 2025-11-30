<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Services\SMSNotificationService;

class TransactionNotificationWAController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    // Send notification for a transaction update
    public function notifyTransaction($transactionId)
    {
        $transaction = Transaction::findOrFail($transactionId);
        $user = $transaction->user; 

        $message = "Your transaction #{$transaction->id} is now '{$transaction->status}'";

        $this->whatsapp->sendWhatsAppMessage($user->phone_number, $message);

        $transaction->update(['notification_sent' => true]);

        return response()->json(['status' => 'success', 'message' => 'Notification sent']);
    }
}