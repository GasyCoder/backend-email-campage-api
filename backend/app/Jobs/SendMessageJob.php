<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Message;
use App\Services\Email\EmailProviderFactory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function handle(EmailProviderFactory $factory): void
    {
        $message = Message::query()->findOrFail($this->messageId);

        if ($message->status !== 'queued') {
            return;
        }

        try {
            $provider = $factory->make();
            $result = $provider->send($message);

            $message->provider = $result['provider'];
            $message->provider_message_id = $result['provider_message_id'];
            $message->status = 'sent';
            $message->sent_at = Carbon::now();
            $message->last_error = null;
            $message->save();

            Event::create([
                'workspace_id' => $message->workspace_id,
                'message_id' => $message->id,
                'type' => 'sent',
                'payload' => ['provider' => $message->provider],
                'occurred_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            $message->status = 'failed';
            $message->last_error = $e->getMessage();
            $message->save();

            Event::create([
                'workspace_id' => $message->workspace_id,
                'message_id' => $message->id,
                'type' => 'failed',
                'payload' => ['error' => $e->getMessage()],
                'occurred_at' => Carbon::now(),
            ]);
        }
    }
}
