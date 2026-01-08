<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Message;
use App\Models\TrackingLink;
use Carbon\Carbon;

class ClickController extends Controller
{
    public function __invoke(int $messageId, string $hash)
    {
        $message = Message::query()->findOrFail($messageId);

        $link = TrackingLink::query()
            ->where('message_id', $message->id)
            ->where('hash', $hash)
            ->firstOrFail();

        Event::create([
            'workspace_id' => $message->workspace_id,
            'message_id' => $message->id,
            'type' => 'click',
            'payload' => ['hash' => $hash, 'url' => $link->url],
            'occurred_at' => Carbon::now(),
        ]);

        return redirect()->away($link->url);
    }
}
