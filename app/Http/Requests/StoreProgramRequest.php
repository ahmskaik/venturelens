<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->canManageOrganization();
    }

    public function rules(): array
    {
        $organization = $this->user()?->primaryOrganization();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('programs', 'slug')->where('organization_id', $organization?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'archived'])],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'max_applications' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This apply-link slug is already used by another cohort.',
        ];
    }
}
