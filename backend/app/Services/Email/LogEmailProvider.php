<?php

namespace App\Services\Email;

use App\Models\Message;
use Illuminate\Support\Facades\Log;

class LogEmailProvider implements EmailProvider
{
    public function send(Message $message): array
    {
        Log::info('LOG_EMAIL_PROVIDER', [
            'to' => $message->to_email,
            'subject' => $message->subject,
        ]);

        return ['provider' => 'log', 'provider_message_id' => null];
    }
}
