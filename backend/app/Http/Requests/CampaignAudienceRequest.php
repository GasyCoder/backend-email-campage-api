<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignAudienceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'list_ids' => ['required','array','min:1'],
            'list_ids.*' => ['integer','min:1'],
        ];
    }
}
