<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignAudienceRequest;
use App\Http\Requests\CampaignScheduleRequest;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Jobs\PrepareCampaignSendJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\EmailTemplate;
use App\Models\MailingList;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CampaignsController extends Controller
{
    public function index(Request $request)
    {
        $workspace = app('workspace');

        $q = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->with('lists:id,name', 'template:id,name')
            ->orderByDesc('id');

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json($q->paginate(20));
    }

    public function store(StoreCampaignRequest $request)
    {
        $workspace = app('workspace');
        $data = $request->validated();

        if (!empty($data['email_template_id'])) {
            $this->assertTemplateAccessible($workspace->id, (int)$data['email_template_id']);
        }

        $campaign = Campaign::create([
            'workspace_id' => $workspace->id,
            'status' => 'draft',
            ...$data,
        ]);

        return response()->json(['campaign' => $campaign], 201);
    }

    public function show($id)
    {
        $workspace = app('workspace');

        $campaign = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->with('lists', 'template')
            ->findOrFail($id);

        return response()->json(['campaign' => $campaign]);
    }

    public function update(UpdateCampaignRequest $request, $id)
    {
        $workspace = app('workspace');
        $data = $request->validated();

        $campaign = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        if (!empty($data['email_template_id'])) {
            $this->assertTemplateAccessible($workspace->id, (int)$data['email_template_id']);
        }

        $campaign->fill($data);
        $campaign->save();

        return response()->json(['campaign' => $campaign->load('lists','template')]);
    }

    public function audience(CampaignAudienceRequest $request, $id)
    {
        $workspace = app('workspace');

        $campaign = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $listIds = $request->validated()['list_ids'];

        // Validate lists belong to workspace
        $validIds = MailingList::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $listIds)
            ->pluck('id')
            ->all();

        if (count($validIds) === 0) {
            return response()->json(['message' => 'No valid lists provided.'], 422);
        }

        $campaign->lists()->sync($validIds);

        return response()->json([
            'message' => 'Audience updated',
            'lists' => $campaign->lists()->get(['id','name']),
        ]);
    }

    public function preview(Request $request, $id)
    {
        $workspace = app('workspace');

        $campaign = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->with('template')
            ->findOrFail($id);

        // Choose a sample contact (first active) or create a fake one
        $contact = Contact::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        $sample = $contact ?: new Contact([
            'email' => 'sample@example.com',
            'first_name' => 'Sample',
            'last_name' => 'User',
        ]);

        $html = $campaign->html_body;

        // If html_body is empty, fall back to template html
        if (!$html && $campaign->template) {
            $html = $campaign->template->html;
        }

        $html = $html ?: '<p>Hello {{first_name}},</p><p>{{message}}</p>';

        // Allow passing preview variables (title/message/cta...) for quick render
        $vars = $request->validate([
            'vars' => ['sometimes','array'],
        ])['vars'] ?? [];

        $rendered = $this->renderPlaceholders($html, $sample, $vars);

        return response()->json([
            'campaign_id' => $campaign->id,
            'sample_contact' => [
                'email' => $sample->email,
                'first_name' => $sample->first_name,
                'last_name' => $sample->last_name,
            ],
            'html' => $rendered,
        ]);
    }

    public function schedule(CampaignScheduleRequest $request, $id)
    {
        $workspace = app('workspace');

        $campaign = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $scheduledAt = Carbon::parse($request->validated()['scheduled_at']);

        if ($scheduledAt->isPast()) {
            return response()->json(['message' => 'scheduled_at must be in the future.'], 422);
        }

        $campaign->scheduled_at = $scheduledAt;
        $campaign->status = 'scheduled';
        $campaign->save();

        return response()->json([
            'message' => 'Campaign scheduled',
            'campaign' => $campaign,
        ]);
    }

    private function assertTemplateAccessible(int $workspaceId, int $templateId): void
    {
        EmailTemplate::query()
            ->where('id', $templateId)
            ->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')
                  ->orWhere('workspace_id', $workspaceId);
            })
            ->firstOrFail();
    }

    private function renderPlaceholders(string $html, Contact $contact, array $vars = []): string
    {
        $base = [
            'email' => $contact->email,
            'first_name' => $contact->first_name ?? '',
            'last_name' => $contact->last_name ?? '',
        ];

        // Merge vars (title/message/cta_url...) without breaking base
        $data = array_merge($vars, $base);

        // Replace {{key}} occurrences
        foreach ($data as $key => $value) {
            $safe = is_scalar($value) ? (string)$value : '';
            $html = str_replace('{{'.$key.'}}', $safe, $html);
        }

        return $html;
    }


    public function sendNow($id)
    {
        $workspace = app('workspace');

        $campaign = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->withCount('lists')
            ->findOrFail($id);

        if ($campaign->lists_count <= 0) {
            return response()->json(['message' => 'Campaign has no audience.'], 422);
        }

        if (!in_array($campaign->status, ['draft','scheduled'], true)) {
            return response()->json(['message' => 'Campaign cannot be sent in current status.'], 422);
        }

        PrepareCampaignSendJob::dispatch($campaign->id);

        return response()->json(['message' => 'Send queued.'], 202);
    }

    public function stats($id)
    {
        $workspace = app('workspace');

        $campaign = Campaign::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $counts = Message::query()
            ->where('workspace_id', $workspace->id)
            ->where('campaign_id', $campaign->id)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return response()->json([
            'campaign_id' => $campaign->id,
            'counts' => $counts,
        ]);
    }
}
