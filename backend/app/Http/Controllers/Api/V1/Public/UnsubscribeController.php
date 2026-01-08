<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function __invoke(Request $request, int $messageId, string $signature)
    {
        $message = Message::query()->findOrFail($messageId);

        $expected = hash_hmac('sha256', (string)$messageId, (string)config('services.unsubscribe.signing_key'));
        if (!hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        if ($message->contact_id) {
            Contact::query()->whereKey($message->contact_id)->update(['status' => 'unsubscribed']);
        }

        $message->status = 'unsubscribed';
        $message->save();

        Event::create([
            'workspace_id' => $message->workspace_id,
            'message_id' => $message->id,
            'type' => 'unsubscribe',
            'payload' => [],
            'occurred_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'You have been unsubscribed.']);
    }
}
