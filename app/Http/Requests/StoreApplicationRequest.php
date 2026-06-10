<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'startup_name' => ['required', 'string', 'max:255'],
            'founder_name' => ['required', 'string', 'max:255'],
            'founder_email' => ['required', 'email', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'stage' => ['required', 'in:idea,mvp,growth'],
            'sector' => ['nullable', 'string', 'max:255'],
            'one_liner' => ['nullable', 'string', 'max:500'],
            'problem' => ['nullable', 'string', 'max:5000'],
            'solution' => ['nullable', 'string', 'max:5000'],
            'market' => ['nullable', 'string', 'max:5000'],
            'traction' => ['nullable', 'string', 'max:5000'],
            'team' => ['nullable', 'string', 'max:5000'],
            'funding_needs' => ['nullable', 'string', 'max:2000'],
            'pitch_deck' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }
}
