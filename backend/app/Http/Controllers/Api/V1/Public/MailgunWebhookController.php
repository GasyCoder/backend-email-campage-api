<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MailgunWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        // MVP: accept payload and map if provider_message_id exists
        $payload = $request->all();

        // Essayez d’extraire l’ID provider Mailgun
        $providerId =
            data_get($payload, 'event-data.message.headers.message-id')
            ?? data_get($payload, 'event-data.message.headers.message_id')
            ?? data_get($payload, 'message-id')
            ?? data_get($payload, 'Message-Id');

        $eventType =
            data_get($payload, 'event-data.event')
            ?? data_get($payload, 'event')
            ?? 'unknown';

        $message = null;
        if ($providerId) {
            $message = Message::query()
                ->where('provider', 'mailgun')
                ->where('provider_message_id', $providerId)
                ->first();
        }

        if ($message) {
            // Map minimal
            if (in_array($eventType, ['delivered'], true)) {
                $message->status = 'delivered';
                $message->delivered_at = Carbon::now();
                $message->save();
            } elseif (in_array($eventType, ['bounced', 'bounce'], true)) {
                $message->status = 'bounced';
                $message->save();
            } elseif (in_array($eventType, ['complained', 'complaint'], true)) {
                $message->status = 'complained';
                $message->save();
            }

            Event::create([
                'workspace_id' => $message->workspace_id,
                'message_id' => $message->id,
                'type' => 'webhook_'.$eventType,
                'payload' => $payload,
                'occurred_at' => Carbon::now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
