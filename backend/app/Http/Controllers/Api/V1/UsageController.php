<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QuotaService;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function __invoke(Request $request, QuotaService $quota)
    {
        $workspace = app('workspace');
        $plan = $quota->getPlan($workspace);
        $usage = $quota->getUsage($workspace);

        return response()->json([
            'period' => $quota->currentPeriod(),
            'plan' => [
                'code' => $plan->code,
                'name' => $plan->name,
                'monthly_credits' => (int)$plan->monthly_credits,
                'max_recipients_per_campaign' => (int)$plan->max_recipients_per_campaign,
                'monthly_recipient_limit' => (int)$plan->monthly_recipient_limit,
            ],
            'usage' => [
                'credits_used' => (int)$usage->credits_used,
                'recipients_sent' => (int)$usage->recipients_sent,
            ],
            'remaining' => [
                'credits' => $quota->remainingCredits($workspace),
                'recipients' => $quota->remainingRecipients($workspace),
            ],
        ]);
    }
}
