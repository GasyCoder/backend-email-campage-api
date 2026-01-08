<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required','string','max:140'],
            'subject' => ['nullable','string','max:190'],
            'preheader' => ['nullable','string','max:190'],
            'from_name' => ['nullable','string','max:120'],
            'from_email' => ['nullable','email','max:190'],
            'reply_to' => ['nullable','email','max:190'],
            'html_body' => ['nullable','string','max:200000'],
            'text_body' => ['nullable','string','max:200000'],
            'email_template_id' => ['nullable','integer'],
        ];
    }
}
