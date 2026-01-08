<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\MailingList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ImportContactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $workspaceId,
        public string $storedPath,
        public ?int $listId = null
    ) {}

    public function handle(): void
    {
        $fullPath = Storage::path($this->storedPath);

        if (!file_exists($fullPath)) {
            return;
        }

        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            return;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return;
        }

        $map = $this->mapHeader($header);

        $list = null;
        if ($this->listId) {
            $list = MailingList::query()
                ->where('workspace_id', $this->workspaceId)
                ->find($this->listId);
        }

        while (($row = fgetcsv($handle)) !== false) {
            $email = $this->get($row, $map, 'email');
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $contact = Contact::updateOrCreate(
                ['workspace_id' => $this->workspaceId, 'email' => strtolower($email)],
                [
                    'first_name' => $this->get($row, $map, 'first_name'),
                    'last_name' => $this->get($row, $map, 'last_name'),
                    'status' => 'active',
                    'source' => 'import',
                ]
            );

            if ($list) {
                $list->contacts()->syncWithoutDetaching([$contact->id]);
            }
        }

        fclose($handle);

        // Optionnel: supprimer fichier import
        Storage::delete($this->storedPath);
    }

    private function mapHeader(array $header): array
    {
        $norm = array_map(fn($h) => strtolower(trim($h)), $header);

        $find = function(array $candidates) use ($norm) {
            foreach ($candidates as $c) {
                $idx = array_search($c, $norm, true);
                if ($idx !== false) return $idx;
            }
            return null;
        };

        return [
            'email' => $find(['email', 'e-mail', 'mail']),
            'first_name' => $find(['first_name', 'firstname', 'prenom', 'prénom', 'first name']),
            'last_name' => $find(['last_name', 'lastname', 'nom', 'last name']),
        ];
    }

    private function get(array $row, array $map, string $key): ?string
    {
        $idx = $map[$key] ?? null;
        if ($idx === null) return null;
        $val = $row[$idx] ?? null;
        $val = is_string($val) ? trim($val) : null;
        return $val === '' ? null : $val;
    }
}
