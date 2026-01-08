<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['sometimes','email','max:190'],
            'first_name' => ['sometimes','nullable','string','max:120'],
            'last_name' => ['sometimes','nullable','string','max:120'],
            'status' => ['sometimes','in:active,unsubscribed,bounced,complained'],
            'tags' => ['sometimes','array'],
            'tags.*' => ['string','max:60'],
        ];
    }
}
