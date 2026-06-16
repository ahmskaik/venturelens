<?php

namespace Database\Seeders;

use App\Models\AgentExecution;
use App\Models\Application;
use App\Models\BusinessAgent;
use App\Models\FounderProfile;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Rubric;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demoPassword = config('venturelens.demo.password');

        $user = User::firstOrCreate(
            ['email' => config('venturelens.demo.email')],
            [
                'name' => 'Demo Program Manager',
                'password' => Hash::make($demoPassword),
            ]
        );

        $organization = Organization::firstOrCreate(
            ['slug' => 'demo-incubator'],
            [
                'name' => 'Demo Incubator',
                'country_code' => 'TR',
                'website' => 'https://venturelens.app',
                'plan' => 'free',
                'screenings_quota' => 50,
                'screenings_used' => 0,
            ]
        );

        if (! $user->organizations()->where('organizations.id', $organization->id)->exists()) {
            $user->organizations()->attach($organization->id, ['role' => 'owner']);
        }

        $rubric = Rubric::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'General Startup Evaluation',
            ],
            [
                'criteria' => Rubric::defaultCriteria(),
                'is_default' => true,
            ]
        );

        $program = Program::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'slug' => 'summer-2026',
            ],
            [
                'name' => 'Summer 2026 Cohort',
                'description' => 'Demo program for judges — submit an application to trigger live Gemini screening.',
                'opens_at' => now()->subMonth(),
                'closes_at' => now()->addMonths(3),
                'max_applications' => 100,
                'status' => 'open',
                'rubric_id' => $rubric->id,
            ]
        );

        Application::firstOrCreate(
            [
                'program_id' => $program->id,
                'startup_name' => 'Sample Startup (pending screen)',
            ],
            [
                'founder_name' => 'Alex Founder',
                'founder_email' => 'founder@example.com',
                'country_code' => 'TR',
                'stage' => 'mvp',
                'sector' => 'fintech',
                'form_data' => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'AI-powered financial literacy for emerging markets',
                        'website' => 'https://sample-startup.example.com',
                        'business_type' => 'b2c',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'no',
                    ],
                    'business' => [
                        'business_model' => 'saas',
                        'target_customers' => 'Young adults in MENA',
                        'founding_year' => 2024,
                        'business_model_summary' => 'Freemium app with premium learning paths.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'no',
                        'received_funding' => 'no',
                        'funding_needs' => '$150K pre-seed',
                    ],
                    'team' => [
                        'co_founder_count' => 2,
                        'team_member_count' => 1,
                        'application_reason' => 'Access to mentor network and cohort visibility.',
                    ],
                    'narrative' => [
                        'problem' => 'Millions lack access to basic financial education.',
                        'solution' => 'Mobile app with Gemini-personalized learning paths.',
                        'market' => '$12B TAM in MENA fintech education',
                        'traction' => '2,000 beta users, 3 pilot partnerships',
                    ],
                ],
                'status' => 'submitted',
                'submitted_at' => now()->subDay(),
            ]
        );

        foreach ([
            ['name' => 'growth', 'autonomy_level' => 1, 'daily_action_cap' => 5],
            ['name' => 'onboarding', 'autonomy_level' => 2, 'daily_action_cap' => 20],
            ['name' => 'support', 'autonomy_level' => 3, 'daily_action_cap' => 50],
            ['name' => 'screening', 'autonomy_level' => 3, 'daily_action_cap' => 1000],
            ['name' => 'finance', 'autonomy_level' => 3, 'daily_action_cap' => 100],
            ['name' => 'success', 'autonomy_level' => 2, 'daily_action_cap' => 30],
        ] as $agent) {
            BusinessAgent::firstOrCreate(
                ['name' => $agent['name']],
                array_merge($agent, ['enabled' => true])
            );
        }

        $this->seedDemoAgentExecutions($organization);
        $this->seedDemoFounder();
    }

    private function seedDemoFounder(): void
    {
        $founder = User::firstOrCreate(
            ['email' => config('venturelens.demo.founder_email')],
            [
                'name' => 'Alex Founder',
                'password' => Hash::make(config('venturelens.demo.founder_password')),
                'account_type' => 'founder',
            ]
        );

        FounderProfile::firstOrCreate(
            ['user_id' => $founder->id],
            ['default_country_code' => 'TR']
        );

        Application::where('founder_email', $founder->email)
            ->whereNull('founder_user_id')
            ->update(['founder_user_id' => $founder->id]);
    }

    private function seedDemoAgentExecutions(Organization $organization): void
    {
        if (AgentExecution::exists()) {
            return;
        }

        $samples = [
            ['agent' => 'screening', 'step' => 'gemini_screen', 'decision' => 'score_78', 'action' => 'Screened application with Gemini', 'level' => 3, 'minutes' => 45],
            ['agent' => 'screening', 'step' => 'quota_check', 'decision' => 'allow', 'action' => 'Quota available — proceeding', 'level' => 3, 'minutes' => 0],
            ['agent' => 'growth', 'step' => 'growth_outreach_drafted', 'decision' => 'draft_outreach', 'action' => 'Drafted outreach to Istanbul University Innovation Hub', 'level' => 1, 'minutes' => 20],
            ['agent' => 'growth', 'step' => 'growth_outreach_drafted', 'decision' => 'draft_outreach', 'action' => 'Drafted outreach to MENA Startup Accelerator', 'level' => 1, 'minutes' => 20],
            ['agent' => 'support', 'step' => 'support_ticket_handled', 'decision' => 'auto_resolve', 'action' => 'Auto-answered billing FAQ', 'level' => 3, 'minutes' => 15],
            ['agent' => 'support', 'step' => 'support_ticket_handled', 'decision' => 'escalate_to_human', 'action' => 'Escalated custom integration question', 'level' => 1, 'minutes' => 5],
            ['agent' => 'onboarding', 'step' => 'welcome_sequence', 'decision' => 'send_welcome', 'action' => 'Sent onboarding checklist (approved by owner)', 'level' => 2, 'minutes' => 10],
            ['agent' => 'onboarding', 'step' => 'program_setup', 'decision' => 'suggest_rubric', 'action' => 'Suggested default rubric template', 'level' => 1, 'minutes' => 8],
            ['agent' => 'finance', 'step' => 'stripe_reconcile', 'decision' => 'classify_arms_length', 'action' => 'Auto-classified Stripe charge as arms-length', 'level' => 3, 'minutes' => 5],
            ['agent' => 'finance', 'step' => 'stripe_reconcile', 'decision' => 'classify_related_party', 'action' => 'Flagged demo org charge as related-party', 'level' => 3, 'minutes' => 5],
            ['agent' => 'success', 'step' => 'health_check', 'decision' => 'nudge_low_usage', 'action' => 'Suggested screening 3 pending applications', 'level' => 2, 'minutes' => 12],
            ['agent' => 'success', 'step' => 'renewal_reminder', 'decision' => 'draft_renewal', 'action' => 'Drafted renewal email for Starter plan', 'level' => 1, 'minutes' => 15],
            ['agent' => 'screening', 'step' => 'pdf_extract', 'decision' => 'extract_ok', 'action' => 'Extracted pitch deck text', 'level' => 3, 'minutes' => 5],
            ['agent' => 'support', 'step' => 'support_ticket_handled', 'decision' => 'auto_resolve', 'action' => 'Auto-answered quota question', 'level' => 3, 'minutes' => 15],
            ['agent' => 'growth', 'step' => 'content_draft', 'decision' => 'draft_blog', 'action' => 'Drafted blog post on fair startup selection', 'level' => 1, 'minutes' => 25],
        ];

        foreach ($samples as $i => $sample) {
            AgentExecution::create([
                'organization_id' => $organization->id,
                'agent_name' => $sample['agent'],
                'step' => $sample['step'],
                'decision' => $sample['decision'],
                'action_taken' => $sample['action'],
                'autonomy_level' => $sample['level'],
                'confidence' => 0.75 + ($i % 3) * 0.08,
                'human_minutes_saved' => $sample['minutes'],
                'status' => 'completed',
                'metadata' => ['seeded' => true],
                'created_at' => now()->subHours(count($samples) - $i),
            ]);
        }
    }
}
