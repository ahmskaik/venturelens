<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $stages = array_keys(config('venturelens.project_profile.stages', []));
        $businessTypes = array_keys(config('venturelens.project_profile.business_types', []));
        $operatingStatuses = array_keys(config('venturelens.project_profile.operating_statuses', []));
        $businessModels = array_keys(config('venturelens.project_profile.business_models', []));

        return [
            'startup_name' => ['required', 'string', 'max:255'],
            'founder_name' => ['required', 'string', 'max:255'],
            'founder_email' => ['required', 'email', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'stage' => ['required', Rule::in($stages ?: ['idea', 'mvp', 'growth'])],
            'sector' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'one_liner' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'pitch_deck_youtube_url' => ['nullable', 'url', 'max:255'],
            'business_type' => ['nullable', Rule::in($businessTypes)],
            'operating_status' => ['nullable', Rule::in($operatingStatuses)],
            'legally_incorporated' => ['nullable', Rule::in(['yes', 'no', '1', '0', 1, 0, true, false])],
            'business_model' => ['nullable', Rule::in($businessModels)],
            'target_customers' => ['nullable', 'string', 'max:5000'],
            'primary_revenue_source' => ['nullable', 'string', 'max:500'],
            'secondary_revenue_source' => ['nullable', 'string', 'max:500'],
            'founding_year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'business_model_summary' => ['nullable', 'string', 'max:5000'],
            'revenue_generating' => ['nullable', Rule::in(['yes', 'no', '1', '0', 1, 0, true, false])],
            'received_funding' => ['nullable', Rule::in(['yes', 'no', '1', '0', 1, 0, true, false])],
            'burn_rate_usd' => ['nullable', 'numeric', 'min:0'],
            'runway_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'revenue_goal_usd' => ['nullable', 'numeric', 'min:0'],
            'funds_use' => ['nullable', 'string', 'max:5000'],
            'co_founder_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'team_member_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'application_reason' => ['nullable', 'string', 'max:5000'],
            'awards' => ['nullable', 'string', 'max:5000'],
            'story' => ['nullable', 'string', 'max:10000'],
            'problem' => ['nullable', 'string', 'max:5000'],
            'solution' => ['nullable', 'string', 'max:5000'],
            'market' => ['nullable', 'string', 'max:5000'],
            'traction' => ['nullable', 'string', 'max:5000'],
            'team' => ['nullable', 'string', 'max:5000'],
            'funding_needs' => ['nullable', 'string', 'max:2000'],
            'pitch_deck' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
