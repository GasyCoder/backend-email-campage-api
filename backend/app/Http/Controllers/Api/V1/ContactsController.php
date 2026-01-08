<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Jobs\ImportContactsJob;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContactsController extends Controller
{
    public function index(Request $request)
    {
        $workspace = app('workspace');

        $q = Contact::query()->where('workspace_id', $workspace->id)->with('tags');

        if ($search = $request->query('search')) {
            $q->where(function ($sub) use ($search) {
                $sub->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        if ($tag = $request->query('tag')) {
            $q->whereHas('tags', fn($t) => $t->where('name', $tag));
        }

        return response()->json($q->orderByDesc('id')->paginate(20));
    }

    public function store(StoreContactRequest $request)
    {
        $workspace = app('workspace');
        $data = $request->validated();

        $contact = Contact::updateOrCreate(
            ['workspace_id' => $workspace->id, 'email' => strtolower($data['email'])],
            [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'status' => 'active',
                'source' => 'manual',
            ]
        );

        if (isset($data['tags'])) {
            $tagIds = $this->upsertTags($workspace->id, $data['tags']);
            $contact->tags()->sync($tagIds);
        }

        return response()->json(['contact' => $contact->load('tags')], 201);
    }

    public function show($id)
    {
        $workspace = app('workspace');

        $contact = Contact::query()
            ->where('workspace_id', $workspace->id)
            ->with('tags', 'lists')
            ->findOrFail($id);

        return response()->json(['contact' => $contact]);
    }

    public function update(UpdateContactRequest $request, $id)
    {
        $workspace = app('workspace');
        $data = $request->validated();

        $contact = Contact::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        $contact->fill($data);
        $contact->save();

        if (array_key_exists('tags', $data)) {
            $tagIds = $this->upsertTags($workspace->id, $data['tags'] ?? []);
            $contact->tags()->sync($tagIds);
        }

        return response()->json(['contact' => $contact->load('tags')]);
    }

    public function destroy($id)
    {
        $workspace = app('workspace');

        $contact = Contact::query()
            ->where('workspace_id', $workspace->id)
            ->findOrFail($id);

        $contact->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function importCsv(Request $request)
    {
        $workspace = app('workspace');

        $request->validate([
            'file' => ['required','file','mimes:csv,txt','max:5120'],
            'list_id' => ['nullable','integer'],
        ]);

        $path = $request->file('file')->store("imports/{$workspace->id}");

        ImportContactsJob::dispatch(
            workspaceId: $workspace->id,
            storedPath: $path,
            listId: $request->integer('list_id')
        );

        return response()->json([
            'message' => 'Import queued',
            'path' => $path,
        ], 202);
    }

    private function upsertTags(int $workspaceId, array $names): array
    {
        $names = collect($names)
            ->map(fn($n) => trim(mb_strtolower($n)))
            ->filter()
            ->unique()
            ->take(20)
            ->values();

        $ids = [];
        foreach ($names as $name) {
            $tag = Tag::updateOrCreate(
                ['workspace_id' => $workspaceId, 'name' => $name],
                ['name' => $name]
            );
            $ids[] = $tag->id;
        }
        return $ids;
    }
}
