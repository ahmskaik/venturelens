<?php

namespace App\Support;

class ApplicationProfile
{
    public const VERSION = 2;

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function buildFromValidated(array $validated): array
    {
        $shortDescription = $validated['short_description']
            ?? $validated['one_liner']
            ?? null;

        return [
            'profile_version' => self::VERSION,
            'basic' => self::filterNulls([
                'short_description' => $shortDescription,
                'website' => $validated['website'] ?? null,
                'pitch_deck_youtube_url' => $validated['pitch_deck_youtube_url'] ?? null,
                'business_type' => $validated['business_type'] ?? null,
                'operating_status' => $validated['operating_status'] ?? null,
                'legally_incorporated' => self::normalizeYesNo($validated['legally_incorporated'] ?? null),
            ]),
            'business' => self::filterNulls([
                'business_model' => $validated['business_model'] ?? null,
                'target_customers' => $validated['target_customers'] ?? null,
                'primary_revenue_source' => $validated['primary_revenue_source'] ?? null,
                'secondary_revenue_source' => $validated['secondary_revenue_source'] ?? null,
                'founding_year' => isset($validated['founding_year']) ? (int) $validated['founding_year'] : null,
                'business_model_summary' => $validated['business_model_summary'] ?? null,
            ]),
            'funding' => self::filterNulls([
                'revenue_generating' => self::normalizeYesNo($validated['revenue_generating'] ?? null),
                'received_funding' => self::normalizeYesNo($validated['received_funding'] ?? null,
                ),
                'burn_rate_usd' => isset($validated['burn_rate_usd']) ? (float) $validated['burn_rate_usd'] : null,
                'runway_months' => isset($validated['runway_months']) ? (int) $validated['runway_months'] : null,
                'revenue_goal_usd' => isset($validated['revenue_goal_usd']) ? (float) $validated['revenue_goal_usd'] : null,
                'funds_use' => $validated['funds_use'] ?? null,
                'funding_needs' => $validated['funding_needs'] ?? null,
            ]),
            'team' => self::filterNulls([
                'co_founder_count' => isset($validated['co_founder_count']) ? (int) $validated['co_founder_count'] : null,
                'team_member_count' => isset($validated['team_member_count']) ? (int) $validated['team_member_count'] : null,
                'application_reason' => $validated['application_reason'] ?? null,
                'awards' => $validated['awards'] ?? null,
                'story' => $validated['story'] ?? null,
                'team_description' => $validated['team'] ?? null,
            ]),
            'narrative' => self::filterNulls([
                'problem' => $validated['problem'] ?? null,
                'solution' => $validated['solution'] ?? null,
                'market' => $validated['market'] ?? null,
                'traction' => $validated['traction'] ?? null,
            ]),
        ];
    }

    /**
     * Flatten structured profile for Gemini screening and legacy consumers.
     *
     * @param  array<string, mixed>|null  $formData
     * @return array<string, mixed>
     */
    public static function flattenForScreening(?array $formData): array
    {
        if ($formData === null) {
            return [];
        }

        if (($formData['profile_version'] ?? null) !== self::VERSION) {
            return $formData;
        }

        $flat = [];

        foreach (['basic', 'business', 'funding', 'team', 'narrative'] as $section) {
            foreach ($formData[$section] ?? [] as $key => $value) {
                $flat[$key] = $value;
            }
        }

        if (isset($flat['short_description']) && ! isset($flat['one_liner'])) {
            $flat['one_liner'] = $flat['short_description'];
        }

        return $flat;
    }

    /**
     * @return array<string, mixed>
     */
    public static function unpackToFormPayload(\App\Models\Application $application): array
    {
        $flat = self::flattenForScreening($application->form_data);

        $payload = [
            'startup_name' => $application->startup_name,
            'founder_name' => $application->founder_name,
            'founder_email' => $application->founder_email,
            'country_code' => $application->country_code,
            'stage' => $application->stage,
            'sector' => $application->sector ?? '',
            'short_description' => $flat['short_description'] ?? $flat['one_liner'] ?? '',
            'website' => $flat['website'] ?? '',
            'pitch_deck_youtube_url' => $flat['pitch_deck_youtube_url'] ?? '',
            'business_type' => $flat['business_type'] ?? 'b2b',
            'operating_status' => $flat['operating_status'] ?? 'active',
            'legally_incorporated' => $flat['legally_incorporated'] ?? 'no',
            'business_model' => $flat['business_model'] ?? 'saas',
            'target_customers' => $flat['target_customers'] ?? '',
            'primary_revenue_source' => $flat['primary_revenue_source'] ?? '',
            'secondary_revenue_source' => $flat['secondary_revenue_source'] ?? '',
            'founding_year' => $flat['founding_year'] ?? '',
            'business_model_summary' => $flat['business_model_summary'] ?? '',
            'revenue_generating' => $flat['revenue_generating'] ?? 'no',
            'received_funding' => $flat['received_funding'] ?? 'no',
            'burn_rate_usd' => $flat['burn_rate_usd'] ?? '',
            'runway_months' => $flat['runway_months'] ?? '',
            'revenue_goal_usd' => $flat['revenue_goal_usd'] ?? '',
            'funds_use' => $flat['funds_use'] ?? '',
            'funding_needs' => $flat['funding_needs'] ?? '',
            'co_founder_count' => $flat['co_founder_count'] ?? '',
            'team_member_count' => $flat['team_member_count'] ?? '',
            'application_reason' => $flat['application_reason'] ?? '',
            'awards' => $flat['awards'] ?? '',
            'story' => $flat['story'] ?? '',
            'problem' => $flat['problem'] ?? '',
            'solution' => $flat['solution'] ?? '',
            'market' => $flat['market'] ?? '',
            'traction' => $flat['traction'] ?? '',
            'team' => $flat['team_description'] ?? $flat['team'] ?? '',
        ];

        foreach ($payload as $key => $value) {
            if ($value === null) {
                $payload[$key] = '';
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>|null  $formData
     * @return list<array{key: string, title: string, fields: list<array{key: string, label: string, value: mixed, type: string}>}>
     */
    public static function displaySections(?array $formData): array
    {
        if ($formData === null) {
            return [];
        }

        if (($formData['profile_version'] ?? null) !== self::VERSION) {
            return [[
                'key' => 'legacy',
                'title' => 'Application responses',
                'fields' => collect($formData)->map(fn ($value, $key) => [
                    'key' => $key,
                    'label' => self::humanize($key),
                    'value' => $value,
                    'type' => 'text',
                ])->values()->all(),
            ]];
        }

        $sections = [
            'basic' => 'Basic information',
            'business' => 'Business information',
            'funding' => 'Funding',
            'team' => 'Team & project story',
            'narrative' => 'Problem & traction',
        ];

        $labels = self::fieldLabels();

        return collect($sections)
            ->map(function (string $title, string $key) use ($formData, $labels) {
                $fields = collect($formData[$key] ?? [])
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->map(function ($value, $fieldKey) use ($labels) {
                        return [
                            'key' => $fieldKey,
                            'label' => $labels[$fieldKey] ?? self::humanize($fieldKey),
                            'value' => self::formatDisplayValue($fieldKey, $value),
                            'type' => self::fieldType($fieldKey, $value),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $key,
                    'title' => $title,
                    'fields' => $fields,
                ];
            })
            ->filter(fn (array $section) => count($section['fields']) > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function fieldLabels(): array
    {
        return [
            'short_description' => 'Short description',
            'website' => 'Website',
            'pitch_deck_youtube_url' => 'YouTube pitch deck',
            'business_type' => 'Type',
            'operating_status' => 'Operating status',
            'legally_incorporated' => 'Legally incorporated',
            'business_model' => 'Business model',
            'target_customers' => 'Target customers',
            'primary_revenue_source' => 'Primary revenue source',
            'secondary_revenue_source' => 'Secondary revenue source',
            'founding_year' => 'Founding year',
            'business_model_summary' => 'How you make money',
            'revenue_generating' => 'Revenue generating',
            'received_funding' => 'Received funding',
            'burn_rate_usd' => 'Burn rate (USD/mo)',
            'runway_months' => 'Runway (months)',
            'revenue_goal_usd' => 'Revenue goal (USD)',
            'funds_use' => 'Use of funds',
            'funding_needs' => 'Funding needs',
            'co_founder_count' => 'Co-founders',
            'team_member_count' => 'Team members (non-founders)',
            'application_reason' => 'Why are you applying?',
            'awards' => 'Awards & recognition',
            'story' => 'Your story',
            'team_description' => 'Team overview',
            'problem' => 'Problem',
            'solution' => 'Solution',
            'market' => 'Market',
            'traction' => 'Traction',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function filterNulls(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }

    private static function normalizeYesNo(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($value, [true, 1, '1', 'yes', 'Yes', 'YES'], true)) {
            return 'yes';
        }

        if (in_array($value, [false, 0, '0', 'no', 'No', 'NO'], true)) {
            return 'no';
        }

        return is_string($value) ? strtolower($value) : null;
    }

    private static function humanize(string $key): string
    {
        return ucfirst(str_replace('_', ' ', $key));
    }

    private static function fieldType(string $key, mixed $value): string
    {
        if (in_array($key, ['website', 'pitch_deck_youtube_url'], true)) {
            return 'url';
        }

        if (in_array($key, ['problem', 'solution', 'market', 'traction', 'story', 'application_reason', 'awards', 'funds_use', 'business_model_summary', 'target_customers', 'team_description', 'short_description'], true)) {
            return 'textarea';
        }

        if (in_array($key, ['burn_rate_usd', 'revenue_goal_usd', 'founding_year', 'runway_months', 'co_founder_count', 'team_member_count'], true)) {
            return 'number';
        }

        if (in_array($key, ['revenue_generating', 'received_funding', 'legally_incorporated'], true)) {
            return 'boolean';
        }

        return 'text';
    }

    private static function formatDisplayValue(string $key, mixed $value): mixed
    {
        if (in_array($key, ['revenue_generating', 'received_funding', 'legally_incorporated'], true)) {
            return $value === 'yes' ? 'Yes' : 'No';
        }

        if (in_array($key, ['business_type', 'business_model', 'operating_status'], true) && is_string($value)) {
            $maps = array_merge(
                config('venturelens.project_profile.business_types', []),
                config('venturelens.project_profile.business_models', []),
                config('venturelens.project_profile.operating_statuses', []),
            );

            return $maps[$value] ?? self::humanize($value);
        }

        if ($key === 'burn_rate_usd' || $key === 'revenue_goal_usd') {
            return '$'.number_format((float) $value, 0);
        }

        return $value;
    }
}
