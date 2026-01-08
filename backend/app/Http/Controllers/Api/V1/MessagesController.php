<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function index(Request $request)
    {
        $workspace = app('workspace');

        $q = Message::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('id');

        if ($campaignId = $request->query('campaign_id')) {
            $q->where('campaign_id', (int)$campaignId);
        }

        if ($sendId = $request->query('send_id')) {
            $q->where('campaign_send_id', (int)$sendId);
        }

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $q->where('to_email', 'like', "%{$search}%");
        }

        return response()->json($q->paginate(25));
    }

    public function show($id)
    {
        $workspace = app('workspace');

        $message = Message::query()
            ->where('workspace_id', $workspace->id)
            ->with('events:id,message_id,type,occurred_at', 'trackingLinks:id,message_id,hash,url')
            ->findOrFail($id);

        return response()->json(['message' => $message]);
    }
}
