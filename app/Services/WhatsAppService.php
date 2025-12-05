<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $client;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.auth_token');
        
        if (empty($sid) || empty($token)) {
            Log::error('Twilio credentials are missing');
            throw new \Exception('Twilio credentials not configured');
        }

        $this->client = new Client($sid, $token);
    }

    public function sendWhatsAppMessage($to, $message)
    {
        try {
            $from = config('services.twilio.whatsapp_from');
            
            if (empty($from)) {
                Log::error('TWILIO_WHATSAPP_FROM is not set');
                return false;
            }

            Log::info("Sending WhatsApp message", [
                'to' => $to,
                'from' => $from,
                'message' => $message
            ]);

            return $this->client->messages->create(
                "whatsapp:" . $to,
                [
                    "from" => $from,
                    "body" => $message
                ]
            );
        } catch (\Exception $e) {
            Log::error("WhatsApp send error: " . $e->getMessage());
            return false;
        }
    }
}