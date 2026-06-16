<?php

namespace Tests\Unit;

use App\Support\ApplicationProfile;
use Tests\TestCase;

class ApplicationProfileTest extends TestCase
{
    public function test_builds_structured_profile_from_validated_input(): void
    {
        $profile = ApplicationProfile::buildFromValidated([
            'short_description' => 'AI marine restoration platform',
            'website' => 'https://www.my-ocean.com',
            'business_type' => 'b2b',
            'operating_status' => 'active',
            'legally_incorporated' => 'no',
            'business_model' => 'saas',
            'target_customers' => 'Marine researchers and NGOs',
            'founding_year' => 2023,
            'business_model_summary' => 'Subscription access to AI analytics.',
            'revenue_generating' => 'no',
            'received_funding' => 'no',
            'co_founder_count' => 1,
            'team_member_count' => 2,
            'application_reason' => 'Mentorship and network access',
            'story' => 'Started after coral bleaching field work.',
            'problem' => 'Ecosystem degradation',
            'solution' => 'AI monitoring and restoration planning',
        ]);

        $this->assertSame(ApplicationProfile::VERSION, $profile['profile_version']);
        $this->assertSame('AI marine restoration platform', $profile['basic']['short_description']);
        $this->assertSame('yes', ApplicationProfile::buildFromValidated(['legally_incorporated' => 'yes'])['basic']['legally_incorporated']);
        $this->assertSame('saas', $profile['business']['business_model']);
        $this->assertSame(2023, $profile['business']['founding_year']);
        $this->assertSame('no', $profile['funding']['revenue_generating']);
        $this->assertSame(1, $profile['team']['co_founder_count']);
    }

    public function test_flattens_profile_for_gemini_screening(): void
    {
        $profile = ApplicationProfile::buildFromValidated([
            'short_description' => 'FinTech for SMEs',
            'problem' => 'Credit gap',
        ]);

        $flat = ApplicationProfile::flattenForScreening($profile);

        $this->assertSame('FinTech for SMEs', $flat['short_description']);
        $this->assertSame('FinTech for SMEs', $flat['one_liner']);
        $this->assertSame('Credit gap', $flat['problem']);
    }

    public function test_display_sections_include_labeled_fields(): void
    {
        $profile = ApplicationProfile::buildFromValidated([
            'short_description' => 'Ocean AI',
            'website' => 'https://example.com',
            'revenue_generating' => 'yes',
        ]);

        $sections = ApplicationProfile::displaySections($profile);

        $this->assertNotEmpty($sections);
        $basic = collect($sections)->firstWhere('key', 'basic');
        $this->assertNotNull($basic);
        $this->assertTrue(collect($basic['fields'])->contains(fn ($f) => $f['label'] === 'Short description'));
    }

    public function test_legacy_form_data_still_displays(): void
    {
        $sections = ApplicationProfile::displaySections([
            'one_liner' => 'Legacy startup',
            'problem' => 'Old format',
        ]);

        $this->assertSame('legacy', $sections[0]['key']);
        $this->assertSame('Legacy startup', $sections[0]['fields'][0]['value']);
    }
}
