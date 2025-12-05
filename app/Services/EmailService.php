<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
      Send agent approval email
     */
    public function sendAgentApproval($user, $payload)
    {
        Mail::send('emails.agent_approval', [
            'payload' => $payload,
            'user'    => $user
        ], function ($msg) use ($user, $payload) {
            $msg->to($user->email)
                ->subject($payload['subject'] ?? 'Agent Approval');
        });
    }

    /**
      Send agent signup email
     */
    public function sendAgentSignup($user, $payload)
    {
        Mail::send('emails.agent_signup', [
            'payload' => $payload,
            'user'    => $user
        ], function ($msg) use ($user, $payload) {
            $msg->to($user->email)
                ->subject($payload['subject'] ?? 'Agent Signup');
        });
    }

    /**
     Send refund request email (Accepted/Rejected)
     */
    public function sendRefundRequest($user, $payload)
    {
        Mail::send('emails.user_refund', [
            'payload' => $payload,
            'user'    => $user
        ], function ($msg) use ($user, $payload) {
            $msg->to($user->email)
                ->subject($payload['subject'] ?? 'Refund Request');
        });
    }

    /**
     Send user signup email
     */
    public function sendUserSignup($user, $payload)
    {
        Mail::send('emails.user_signup', [
            'payload' => $payload,
            'user'    => $user
        ], function ($msg) use ($user, $payload) {
            $msg->to($user->email)
                ->subject($payload['subject'] ?? 'Welcome!');
        });
    }

    /**
      Send support request email to admin
     */
    public function sendSupportRequest($user, $payload)
    {
        Mail::send('emails.support_request', [
            'payload' => $payload,
            'user'    => $user
        ], function ($msg) use ($user, $payload) {
            $msg->to($user->email)
                ->subject($payload['subject'] ?? 'Support Request');
        });
    }

    /**
      Send wallet-to-person email
     */
    public function sendWalletToPerson($user, $payload, $email)
    {
        Mail::send('emails.wallet_to_person', [
            'payload' => $payload,
            'user'    => $user
        ], function ($msg) use ($email, $payload) {
            $msg->to($email)
                ->subject($payload['subject'] ?? 'Wallet Transfer');
        });
    }
}
