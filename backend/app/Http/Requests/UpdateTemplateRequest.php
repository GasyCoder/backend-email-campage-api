<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes','string','max:120'],
            'category' => ['sometimes','nullable','string','max:60'],
            'html' => ['sometimes','string','max:200000'],
        ];
    }
}
