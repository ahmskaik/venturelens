<?php

namespace App\Services\Integrations;

use App\Support\ApplicationProfile;

class GohortoProfileMapper
{
    /**
     * @param  array<string, mixed>  $profile
     * @return array{
     *     startup_name: string,
     *     founder_name: string,
     *     founder_email: string,
     *     country_code: string,
     *     stage: string,
     *     sector: string|null,
     *     form_data: array<string, mixed>
     * }
     */
    public function map(array $profile): array
    {
        $projectId = (int) ($profile['project_id'] ?? 0);
        $context = $profile['context'] ?? [];
        $basic = $context['basic_info'] ?? [];
        $pitch = $context['pitch_media'] ?? [];
        $funding = $context['funding'] ?? [];
        $team = $context['team'] ?? [];
        $owners = $team['owners'] ?? [];
        $primaryOwner = $owners[0] ?? [];

        $startupName = $this->firstNonEmpty(
            $basic['name'] ?? null,
            $profile['project_name'] ?? null,
            'Gohorto Project '.$projectId,
        );

        $founderName = $this->firstNonEmpty(
            $primaryOwner['name'] ?? null,
            $startupName,
            'Unknown Founder',
        );

        $countryCode = GohortoCountryMapper::resolve(
            $primaryOwner['residency'] ?? null,
            $primaryOwner['nationality'] ?? null,
            $this->countryFromAddress($primaryOwner['address'] ?? null),
        );

        $validated = [
            'startup_name' => $startupName,
            'founder_name' => $founderName,
            'founder_email' => $this->syntheticEmail($projectId),
            'country_code' => $countryCode,
            'stage' => $this->mapStage($basic['stage'] ?? null),
            'sector' => $this->mapSector($context['categories'] ?? []),
            'short_description' => $this->firstNonEmpty($basic['short_description'] ?? null, $basic['description'] ?? null),
            'website' => $pitch['website'] ?? null,
            'pitch_deck_youtube_url' => $pitch['youtube_link'] ?? null,
            'business_type' => $this->mapBusinessType($basic['business_types'] ?? []),
            'operating_status' => $this->mapOperatingStatus($basic['Current business status'] ?? null),
            'legally_incorporated' => $this->mapYesNo($basic['Are you registered or incorporated?'] ?? null),
            'business_model' => 'other',
            'target_customers' => $basic['target_customers'] ?? null,
            'founding_year' => $this->parseYear($basic['founding_year'] ?? null),
            'business_model_summary' => $this->firstNonEmpty(
                $basic['business_summary'] ?? null,
                $basic['executive_summary'] ?? null,
            ),
            'revenue_generating' => $this->mapYesNo($basic['Is the project revenue generating?'] ?? null),
            'received_funding' => $this->mapYesNo($basic['Has received investments?'] ?? null),
            'revenue_goal_usd' => $this->parseMoney($funding['required_fund'] ?? null),
            'funds_use' => $funding['use_of_funds'] ?? null,
            'funding_needs' => $this->formatFundingNeeds($funding),
            'co_founder_count' => $this->parseInt($basic['founders_count'] ?? null),
            'team_member_count' => $this->parseInt($basic['team_size'] ?? null),
            'application_reason' => $basic['why_apply'] ?? null,
            'awards' => $basic['awards'] ?? null,
            'story' => $basic['project_story'] ?? null,
            'problem' => $basic['description'] ?? null,
            'solution' => $basic['executive_summary'] ?? null,
            'market' => $basic['target_customers'] ?? null,
            'traction' => $this->formatTraction($basic, $funding),
            'team' => $this->formatTeam($team),
        ];

        $formData = ApplicationProfile::buildFromValidated($validated);
        $formData['integration'] = [
            'source' => 'gohorto',
            'gohorto_project_id' => $projectId,
            'gohorto_project_name' => $profile['project_name'] ?? $startupName,
            'gohorto_project_type' => $basic['project_type'] ?? null,
            'gohorto_financing' => $basic['financing'] ?? null,
            'gohorto_categories' => $context['categories'] ?? [],
            'gohorto_document_text' => $this->formatDocumentExtracts($context['document_extracts'] ?? []),
            'imported_at' => now()->toIso8601String(),
        ];

        return [
            'startup_name' => $startupName,
            'founder_name' => $founderName,
            'founder_email' => $validated['founder_email'],
            'country_code' => $countryCode,
            'stage' => $validated['stage'],
            'sector' => $validated['sector'],
            'form_data' => $formData,
        ];
    }

    private function syntheticEmail(int $projectId): string
    {
        return 'gohorto-'.$projectId.'@import.venturelens.local';
    }

    private function mapStage(?string $stage): string
    {
        $normalized = strtolower(trim((string) $stage));

        return match (true) {
            str_contains($normalized, 'idea') => 'idea',
            str_contains($normalized, 'pre-seed') || str_contains($normalized, 'pre seed') => 'pre_seed',
            str_contains($normalized, 'seed') && ! str_contains($normalized, 'pre') => 'seed',
            str_contains($normalized, 'mvp') => 'mvp',
            str_contains($normalized, 'product-market') || str_contains($normalized, 'product market') => 'growth',
            str_contains($normalized, 'growth') => 'growth',
            str_contains($normalized, 'scal') => 'scale',
            default => 'mvp',
        };
    }

    /**
     * @param  list<string>  $categories
     */
    private function mapSector(array $categories): ?string
    {
        if ($categories === []) {
            return 'other';
        }

        $label = strtolower(implode(' ', $categories));
        $sectorMap = config('venturelens.project_profile.sectors', []);

        foreach ($sectorMap as $key => $name) {
            if (str_contains($label, strtolower($name)) || str_contains($label, str_replace(' ', '', $key))) {
                return $key;
            }
        }

        if (str_contains($label, 'agro')) {
            return 'agtech';
        }
        if (str_contains($label, 'fintech') || str_contains($label, 'fin tech')) {
            return 'fintech';
        }
        if (str_contains($label, 'health')) {
            return 'healthtech';
        }
        if (str_contains($label, 'clean')) {
            return 'cleantech';
        }
        if (str_contains($label, 'edu')) {
            return 'edtech';
        }

        return 'other';
    }

    /**
     * @param  list<string>  $types
     */
    private function mapBusinessType(array $types): string
    {
        $joined = strtolower(implode(' ', $types));

        if (str_contains($joined, 'b2b2c')) {
            return 'b2b2c';
        }
        if (str_contains($joined, 'marketplace')) {
            return 'marketplace';
        }
        if (str_contains($joined, 'b2b')) {
            return 'b2b';
        }
        if (str_contains($joined, 'b2c')) {
            return 'b2c';
        }

        return 'b2b';
    }

    private function mapOperatingStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        return str_contains($normalized, 'not active') || str_contains($normalized, 'inactive')
            ? 'inactive'
            : 'active';
    }

    private function mapYesNo(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['yes', 'y', '1', 'true'], true) ? 'yes' : 'no';
    }

    private function parseYear(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $year = (int) preg_replace('/\D/', '', (string) $value);

        if ($year < 1900 || $year > (int) date('Y') + 1) {
            return null;
        }

        return $year;
    }

    private function parseInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private function parseMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (float) $value);
    }

    /**
     * @param  array<string, mixed>  $funding
     */
    private function formatFundingNeeds(array $funding): ?string
    {
        $parts = array_filter([
            isset($funding['required_fund']) ? 'Required: $'.$funding['required_fund'] : null,
            isset($funding['received_fund']) ? 'Received: $'.$funding['received_fund'] : null,
            isset($funding['current_value']) ? 'Current value: $'.$funding['current_value'] : null,
        ]);

        return $parts === [] ? null : implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $basic
     * @param  array<string, mixed>  $funding
     */
    private function formatTraction(array $basic, array $funding): ?string
    {
        $parts = array_filter([
            $basic['financing'] ?? null,
            isset($funding['current_value']) ? 'Valuation/current: $'.$funding['current_value'] : null,
            isset($funding['received_fund']) && (float) $funding['received_fund'] > 0
                ? 'Funding received: $'.$funding['received_fund']
                : null,
        ]);

        return $parts === [] ? null : implode("\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $team
     */
    private function formatTeam(array $team): ?string
    {
        $lines = [];

        foreach (array_merge($team['owners'] ?? [], $team['members'] ?? []) as $member) {
            if (! is_array($member)) {
                continue;
            }

            $name = $member['name'] ?? 'Team member';
            $role = $member['Profession'] ?? $member['headline'] ?? $member['role'] ?? null;
            $bio = $member['bio'] ?? null;

            $line = $role ? "{$name} ({$role})" : $name;
            if ($bio) {
                $line .= ': '.$bio;
            }

            $lines[] = $line;
        }

        $lines = array_values(array_unique($lines));

        return $lines === [] ? null : implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $extracts
     */
    private function formatDocumentExtracts(array $extracts): string
    {
        $parts = [];

        foreach ($extracts as $extract) {
            $text = trim((string) ($extract['text'] ?? ''));
            if ($text === '' || $text === '[CV file not found on disk]') {
                continue;
            }

            $label = $extract['name'] ?? $extract['type'] ?? 'document';
            $parts[] = "[{$label}]\n{$text}";
        }

        return implode("\n\n", $parts);
    }

    private function countryFromAddress(?string $address): ?string
    {
        if ($address === null || $address === '') {
            return null;
        }

        $segments = array_map('trim', explode(',', $address));

        return $segments === [] ? null : end($segments);
    }

    private function firstNonEmpty(?string ...$values): string
    {
        foreach ($values as $value) {
            if ($value !== null && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }
}
