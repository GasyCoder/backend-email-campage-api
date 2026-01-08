<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes','string','max:140'],
            'status' => ['sometimes','in:draft,scheduled,sending,sent,paused'],
            'subject' => ['sometimes','nullable','string','max:190'],
            'preheader' => ['sometimes','nullable','string','max:190'],
            'from_name' => ['sometimes','nullable','string','max:120'],
            'from_email' => ['sometimes','nullable','email','max:190'],
            'reply_to' => ['sometimes','nullable','email','max:190'],
            'html_body' => ['sometimes','nullable','string','max:200000'],
            'text_body' => ['sometimes','nullable','string','max:200000'],
            'email_template_id' => ['sometimes','nullable','integer'],
        ];
    }
}
