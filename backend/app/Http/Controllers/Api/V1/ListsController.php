<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkListContactsRequest;
use App\Http\Requests\StoreListRequest;
use App\Http\Requests\UpdateListRequest;
use App\Models\Contact;
use App\Models\MailingList;
use Illuminate\Http\Request;

class ListsController extends Controller
{
    public function index()
    {
        $workspace = app('workspace');

        $lists = MailingList::query()
            ->where('workspace_id', $workspace->id)
            ->withCount('contacts')
            ->orderByDesc('id')
            ->get();

        return response()->json(['lists' => $lists]);
    }

    public function store(StoreListRequest $request)
    {
        $workspace = app('workspace');

        $list = MailingList::create([
            'workspace_id' => $workspace->id,
            'name' => $request->validated()['name'],
            'description' => $request->validated()['description'] ?? null,
        ]);

        return response()->json(['list' => $list], 201);
    }

    public function show($id)
    {
        $workspace = app('workspace');

        $list = MailingList::query()
            ->where('workspace_id', $workspace->id)
            ->with('contacts.tags')
            ->findOrFail($id);

        return response()->json(['list' => $list]);
    }

    public function update(UpdateListRequest $request, $id)
    {
        $workspace = app('workspace');

        $list = MailingList::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $list->fill($request->validated());
        $list->save();

        return response()->json(['list' => $list]);
    }

    public function destroy($id)
    {
        $workspace = app('workspace');

        $list = MailingList::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $list->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function bulkContacts(BulkListContactsRequest $request, $id)
    {
        $workspace = app('workspace');

        $list = MailingList::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $ids = $request->validated()['contact_ids'];

        // Vérifier que les contacts appartiennent au workspace
        $validIds = Contact::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        if ($request->validated()['action'] === 'add') {
            $list->contacts()->syncWithoutDetaching($validIds);
        } else {
            $list->contacts()->detach($validIds);
        }

        return response()->json([
            'message' => 'OK',
            'contacts_count' => $list->contacts()->count(),
        ]);
    }
}
