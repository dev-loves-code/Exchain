<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            env('TWILIO_SID'),
            env('TWILIO_AUTH_TOKEN')
        );
    }

   public function sendWhatsAppMessage($to, $message)
{
    try {
        return $this->client->messages->create(
            "whatsapp:" . $to,
            [
                "from" => env("TWILIO_WHATSAPP_FROM"),
                "body" => $message
            ]
        );
    } catch (\Exception $e) {
        Log::error("WhatsApp send error: " . $e->getMessage());
        return false;
    }
}
}