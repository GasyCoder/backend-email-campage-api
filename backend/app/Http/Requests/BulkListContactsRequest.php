<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkListContactsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'action' => ['required','in:add,remove'],
            'contact_ids' => ['required','array','min:1'],
            'contact_ids.*' => ['integer','min:1'],
        ];
    }
}
