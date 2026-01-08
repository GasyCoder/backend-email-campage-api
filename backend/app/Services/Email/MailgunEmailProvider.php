<?php

namespace App\Services\Email;

use App\Models\Message;
use Illuminate\Support\Facades\Http;

class MailgunEmailProvider implements EmailProvider
{
    public function send(Message $message): array
    {
        $domain = config('services.mailgun.domain');
        $secret = config('services.mailgun.secret');

        if (!$domain || !$secret) {
            throw new \RuntimeException('Mailgun is not configured.');
        }

        $from = trim(($message->from_name ? $message->from_name.' ' : '').'<'.$message->from_email.'>');

        $resp = Http::withBasicAuth('api', $secret)
            ->asForm()
            ->post("https://api.mailgun.net/v3/{$domain}/messages", [
                'from' => $from,
                'to' => $message->to_email,
                'subject' => $message->subject,
                'html' => $message->html_body,
                'text' => $message->text_body,
                'h:Reply-To' => $message->reply_to,
            ]);

        if (!$resp->successful()) {
            throw new \RuntimeException('Mailgun send failed: '.$resp->body());
        }

        $json = $resp->json();
        return [
            'provider' => 'mailgun',
            'provider_message_id' => $json['id'] ?? null,
        ];
    }
}
