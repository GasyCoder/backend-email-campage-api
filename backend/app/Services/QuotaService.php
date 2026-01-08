<?php

namespace App\Services;

use App\Models\CreditLedger;
use App\Models\UsageCounter;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QuotaService
{
    public function currentPeriod(): string
    {
        return Carbon::now()->format('Y-m');
    }

    public function getUsage(Workspace $workspace): UsageCounter
    {
        $period = $this->currentPeriod();

        return UsageCounter::query()->firstOrCreate(
            ['workspace_id' => $workspace->id, 'period' => $period],
            ['credits_used' => 0, 'recipients_sent' => 0]
        );
    }

    public function getPlan(Workspace $workspace)
    {
        $sub = $workspace->subscription()->with('plan')->first();

        if (!$sub || $sub->status !== 'active') {
            throw new HttpException(402, 'No active subscription.');
        }

        return $sub->plan;
    }

    public function remainingCredits(Workspace $workspace): int
    {
        $plan = $this->getPlan($workspace);
        $usage = $this->getUsage($workspace);

        return max(0, (int)$plan->monthly_credits - (int)$usage->credits_used);
    }

    public function remainingRecipients(Workspace $workspace): int
    {
        $plan = $this->getPlan($workspace);
        $usage = $this->getUsage($workspace);

        return max(0, (int)$plan->monthly_recipient_limit - (int)$usage->recipients_sent);
    }

    public function assertCanUseCredits(Workspace $workspace, int $cost): void
    {
        if ($this->remainingCredits($workspace) < $cost) {
            throw new HttpException(402, 'Insufficient AI credits.');
        }
    }

    public function consumeCredits(Workspace $workspace, int $cost, ?string $reason = null, ?string $refType = null, ?int $refId = null): void
    {
        $this->assertCanUseCredits($workspace, $cost);

        DB::transaction(function () use ($workspace, $cost, $reason, $refType, $refId) {
            $usage = $this->getUsage($workspace);
            $usage->increment('credits_used', $cost);

            CreditLedger::create([
                'workspace_id' => $workspace->id,
                'type' => 'use',
                'amount' => -$cost,
                'reason' => $reason,
                'ref_type' => $refType,
                'ref_id' => $refId,
            ]);
        });
    }

    public function assertRecipientsAllowed(Workspace $workspace, int $count): void
    {
        $plan = $this->getPlan($workspace);

        if ($count > (int)$plan->max_recipients_per_campaign) {
            throw new HttpException(402, "Campaign recipient limit exceeded (max {$plan->max_recipients_per_campaign}).");
        }

        if ($this->remainingRecipients($workspace) < $count) {
            throw new HttpException(402, 'Monthly recipient limit exceeded.');
        }
    }

    public function consumeRecipients(Workspace $workspace, int $count, ?string $reason = null, ?string $refType = null, ?int $refId = null): void
    {
        $this->assertRecipientsAllowed($workspace, $count);

        DB::transaction(function () use ($workspace, $count, $reason, $refType, $refId) {
            $usage = $this->getUsage($workspace);
            $usage->increment('recipients_sent', $count);

            CreditLedger::create([
                'workspace_id' => $workspace->id,
                'type' => 'use',
                'amount' => 0,
                'reason' => $reason ?? "Recipients sent: {$count}",
                'ref_type' => $refType,
                'ref_id' => $refId,
            ]);
        });
    }
}
