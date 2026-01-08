<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Message;

class CampaignSendsController extends Controller
{
    public function byCampaign($id)
    {
        $workspace = app('workspace');

        Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $sends = CampaignSend::query()
            ->where('workspace_id', $workspace->id)
            ->where('campaign_id', $id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['sends' => $sends]);
    }

    public function show($id)
    {
        $workspace = app('workspace');

        $send = CampaignSend::query()
            ->where('workspace_id', $workspace->id)
            ->with('campaign:id,name,status')
            ->findOrFail($id);

        $counts = Message::query()
            ->where('workspace_id', $workspace->id)
            ->where('campaign_send_id', $send->id)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return response()->json([
            'send' => $send,
            'message_counts' => $counts,
        ]);
    }
}
