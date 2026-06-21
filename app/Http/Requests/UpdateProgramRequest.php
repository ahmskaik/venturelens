<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! $user->canManageOrganization()) {
            return false;
        }

        $program = $this->route('program');

        if (! $program instanceof Program) {
            return false;
        }

        $organization = $user->primaryOrganization();

        return $organization !== null && $program->organization_id === $organization->id;
    }

    public function rules(): array
    {
        /** @var Program $program */
        $program = $this->route('program');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('programs', 'slug')
                    ->where('organization_id', $program->organization_id)
                    ->ignore($program->id),
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
