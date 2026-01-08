<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Message;
use App\Services\QuotaService;
use App\Services\Tracking\TrackingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PrepareCampaignSendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $campaignId) {}

    public function handle(QuotaService $quota): void
    {
        $campaign = Campaign::query()
            ->with('lists')
            ->findOrFail($this->campaignId);

        if (!in_array($campaign->status, ['draft','scheduled'], true)) {
            return;
        }

        $workspace = $campaign->workspace;

        if ($campaign->lists->isEmpty()) {
            throw new \RuntimeException('Campaign has no audience lists.');
        }

        if (!$campaign->subject || !$campaign->from_email || !$campaign->from_name) {
            throw new \RuntimeException('Campaign missing subject/from fields.');
        }

        $listIds = $campaign->lists->pluck('id')->all();

        // Recipients = contacts active in selected lists (distinct)
        $contactsQuery = Contact::query()
            ->where('workspace_id', $campaign->workspace_id)
            ->where('status', 'active')
            ->whereHas('lists', fn($q) => $q->whereIn('mailing_lists.id', $listIds))
            ->select('contacts.*')
            ->distinct();

        $total = (int) $contactsQuery->count();

        if ($total <= 0) {
            throw new \RuntimeException('No active recipients found.');
        }

        // Quotas (recipients per campaign + monthly)
        $quota->assertRecipientsAllowed($workspace, $total);

        DB::transaction(function () use ($campaign, $quota, $workspace, $total, $contactsQuery) {

            $send = CampaignSend::create([
                'workspace_id' => $campaign->workspace_id,
                'campaign_id' => $campaign->id,
                'status' => 'sending',
                'total_recipients' => $total,
                'started_at' => Carbon::now(),
            ]);

            // consume monthly recipients now (atomique)
            $quota->consumeRecipients($workspace, $total, 'campaign_send', 'campaign_send', $send->id);

            $campaign->status = 'sending';
            $campaign->save();

            // Create messages + enqueue SendMessageJob
            $tracker = app(TrackingService::class);

            $contactsQuery->orderBy('contacts.id')->chunk(500, function ($contacts) use ($campaign, $send, $tracker) {
                foreach ($contacts as $contact) {
                    $html = $campaign->html_body ?: ($campaign->template?->html ?? null);
                    $html = $html ?: '<p>Hello {{first_name}},</p><p>{{message}}</p>';

                    $renderedHtml = $this->renderPlaceholders($html, $contact);
                    $renderedHtml = $this->injectUnsubscribe($renderedHtml, $campaign->id, $contact->id); // placeholder injection later

                    $message = Message::create([
                        'workspace_id' => $campaign->workspace_id,
                        'campaign_id' => $campaign->id,
                        'campaign_send_id' => $send->id,
                        'contact_id' => $contact->id,
                        'to_email' => $contact->email,
                        'subject' => $this->renderSubject($campaign->subject, $contact),
                        'from_name' => $campaign->from_name,
                        'from_email' => $campaign->from_email,
                        'reply_to' => $campaign->reply_to,
                        'html_body' => $renderedHtml,
                        'text_body' => $campaign->text_body,
                        'status' => 'queued',
                    ]);

                    // finalize unsubscribe signature with message id
                    $message->unsubscribe_signature = $this->unsubscribeSignature($message->id);
                    $message->html_body = $this->finalizeUnsubscribeLink($message->html_body, $message->id, $message->unsubscribe_signature);
                    
                    // Tracking (click + open)
                    $message->html_body = $tracker->apply($message, $message->html_body);
                    
                    $message->save();

                    SendMessageJob::dispatch($message->id);
                }
            });

            $send->status = 'completed';
            $send->finished_at = Carbon::now();
            $send->save();

            $campaign->status = 'sent';
            $campaign->save();
        });
    }

    private function renderPlaceholders(string $html, Contact $contact): string
    {
        $data = [
            'email' => $contact->email,
            'first_name' => $contact->first_name ?? '',
            'last_name' => $contact->last_name ?? '',
        ];

        foreach ($data as $key => $value) {
            $html = str_replace('{{'.$key.'}}', (string)$value, $html);
        }

        return $html;
    }

    private function renderSubject(string $subject, Contact $contact): string
    {
        $subject = str_replace('{{first_name}}', (string)($contact->first_name ?? ''), $subject);
        $subject = str_replace('{{last_name}}', (string)($contact->last_name ?? ''), $subject);
        return $subject;
    }

    private function injectUnsubscribe(string $html, int $campaignId, int $contactId): string
    {
        // placeholder token, replaced after Message id is known
        $token = '__UNSUB_LINK__';
        if (str_contains($html, $token)) {
            return $html;
        }

        return $html . '<hr><p style="font-size:12px;color:#666;">Unsubscribe: <a href="__UNSUB_LINK__">click here</a></p>';
    }

    private function finalizeUnsubscribeLink(string $html, int $messageId, string $sig): string
    {
        $url = rtrim(config('app.url'), '/') . "/api/v1/u/{$messageId}/{$sig}";
        return str_replace('__UNSUB_LINK__', $url, $html);
    }

    private function unsubscribeSignature(int $messageId): string
    {
        $key = (string) config('services.unsubscribe.signing_key');
        // APP_KEY peut être "base64:...." -> on garde tel quel (OK pour HMAC)
        return hash_hmac('sha256', (string)$messageId, $key);
    }
}
