<?php

namespace App\Services\Email;

use App\Models\Message;

interface EmailProvider
{
    /** @return array{provider:string, provider_message_id:?string} */
    public function send(Message $message): array;
}
