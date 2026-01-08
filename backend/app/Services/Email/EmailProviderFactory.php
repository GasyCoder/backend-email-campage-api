<?php

namespace App\Services\Email;

class EmailProviderFactory
{
    public function make(): EmailProvider
    {
        return match (config('services.email_provider.driver')) {
            'mailgun' => new MailgunEmailProvider(),
            default => new LogEmailProvider(),
        };
    }
}
