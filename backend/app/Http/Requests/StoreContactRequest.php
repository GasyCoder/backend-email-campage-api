<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required','email','max:190'],
            'first_name' => ['nullable','string','max:120'],
            'last_name' => ['nullable','string','max:120'],
            'tags' => ['array'],
            'tags.*' => ['string','max:60'],
        ];
    }
}
