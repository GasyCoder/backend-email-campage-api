<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Models\EmailTemplate;

class TemplatesController extends Controller
{
    public function index()
    {
        $workspace = app('workspace');

        $templates = EmailTemplate::query()
            ->where(function ($q) use ($workspace) {
                $q->whereNull('workspace_id')
                  ->orWhere('workspace_id', $workspace->id);
            })
            ->orderByRaw('workspace_id is null desc') // globaux d'abord
            ->orderBy('id')
            ->get(['id','workspace_id','name','category','created_at']);

        return response()->json(['templates' => $templates]);
    }

    public function show($id)
    {
        $workspace = app('workspace');

        $template = EmailTemplate::query()
            ->where(function ($q) use ($workspace) {
                $q->whereNull('workspace_id')
                  ->orWhere('workspace_id', $workspace->id);
            })
            ->findOrFail($id);

        return response()->json(['template' => $template]);
    }

    public function store(StoreTemplateRequest $request)
    {
        $workspace = app('workspace');

        $template = EmailTemplate::create([
            'workspace_id' => $workspace->id,
            ...$request->validated(),
        ]);

        return response()->json(['template' => $template], 201);
    }

    public function update(UpdateTemplateRequest $request, $id)
    {
        $workspace = app('workspace');

        $template = EmailTemplate::query()
            ->where('workspace_id', $workspace->id) // sécurité: seuls templates workspace éditables
            ->findOrFail($id);

        $template->fill($request->validated());
        $template->save();

        return response()->json(['template' => $template]);
    }

    public function destroy($id)
    {
        $workspace = app('workspace');

        $template = EmailTemplate::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $template->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
