<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OpenController extends Controller
{
    public function __invoke(Request $request, int $messageId, string $signature)
    {
        $expected = hash_hmac('sha256', (string)$messageId, (string)config('services.unsubscribe.signing_key'));
        if (!hash_equals($expected, $signature)) {
            return response('', 403);
        }

        $message = Message::query()->findOrFail($messageId);

        Event::create([
            'workspace_id' => $message->workspace_id,
            'message_id' => $message->id,
            'type' => 'open',
            'payload' => ['ua' => (string)$request->userAgent()],
            'occurred_at' => Carbon::now(),
        ]);

        // Transparent 1x1 GIF
        $gif = base64_decode('R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
