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

        $this->seedDemoApplications($program);

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

    private function seedDemoApplications(Program $program): void
    {
        $apps = [
            [
                'startup_name'  => 'Harfa',
                'founder_name'  => 'Layla Al-Rashid',
                'founder_email' => 'layla@harfa.io',
                'country_code'  => 'SA',
                'stage'         => 'mvp',
                'sector'        => 'edtech',
                'status'        => 'screened',
                'submitted_at'  => now()->subDays(5),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'Adaptive Arabic literacy platform using AI to personalize reading journeys for K-6 students across the Gulf.',
                        'website' => 'https://harfa.io',
                        'business_type' => 'b2b',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'yes',
                    ],
                    'business' => [
                        'business_model' => 'saas',
                        'target_customers' => 'Private schools and education ministries in KSA, UAE, Kuwait',
                        'founding_year' => 2023,
                        'business_model_summary' => 'Annual per-school licence ($4,800/yr). Ministry bulk deals discounted to $3,200/school.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => '$8,200',
                        'received_funding' => 'yes',
                        'funding_history' => 'SAR 250K angel round (2024)',
                        'funding_needs' => '$500K seed to expand to Egypt and Jordan',
                    ],
                    'team' => [
                        'co_founder_count' => 2,
                        'team_member_count' => 6,
                        'application_reason' => 'Structured programme to navigate MoE procurement and expand regionally.',
                    ],
                    'narrative' => [
                        'problem' => '60% of Arab students read below grade level by age 10. Existing tools are in English or use rote repetition.',
                        'solution' => 'Harfa dynamically adjusts reading difficulty and dialect using an LLM fine-tuned on Modern Standard Arabic + Gulf dialect corpora.',
                        'market' => '$3.4B K-12 edtech TAM in Arab world; $420M addressable in GCC private schools.',
                        'traction' => '14 schools live, 3,200 active students, 91% teacher satisfaction, MOE pilot approved in KSA.',
                        'competition' => 'Noon Academy (social tutoring), Reading Eggs (English-only). No direct Arabic adaptive reading competitor at scale.',
                    ],
                ],
            ],
            [
                'startup_name'  => 'ColdPath Logistics',
                'founder_name'  => 'Mehmet Yılmaz',
                'founder_email' => 'mehmet@coldpath.com.tr',
                'country_code'  => 'TR',
                'stage'         => 'seed',
                'sector'        => 'logistics',
                'status'        => 'screened',
                'submitted_at'  => now()->subDays(4),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'IoT-enabled cold-chain visibility platform for pharmaceutical and food exporters in Turkey.',
                        'website' => 'https://coldpath.com.tr',
                        'business_type' => 'b2b',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'yes',
                    ],
                    'business' => [
                        'business_model' => 'saas',
                        'target_customers' => 'Mid-size pharmaceutical distributors, agri-food exporters (EU-bound shipments)',
                        'founding_year' => 2022,
                        'business_model_summary' => 'SaaS subscription (₺2,800/truck/month) + hardware lease for IoT sensors. NRR 118%.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => '₺380,000 (~$12K)',
                        'received_funding' => 'yes',
                        'funding_history' => '$150K TUBITAK 1512 grant + $80K angel',
                        'funding_needs' => '$1.2M Series A to enter Germany and Poland',
                    ],
                    'team' => [
                        'co_founder_count' => 3,
                        'team_member_count' => 12,
                        'application_reason' => 'EU market entry strategy mentorship and warm intro to logistics VCs in Europe.',
                    ],
                    'narrative' => [
                        'problem' => '30% of pharmaceutical shipments from Turkey to the EU experience undocumented temperature excursions, causing €180M in annual losses.',
                        'solution' => 'ColdPath attaches to any refrigerated truck; sensors stream to cloud dashboard; Gemini flags anomalies and auto-generates EU GDP-compliant reports.',
                        'market' => 'European cold-chain monitoring market €2.1B, growing 14% CAGR.',
                        'traction' => '47 trucks live, 3 pharma clients (including a Pfizer distributor), zero churn in 18 months.',
                        'competition' => 'Sensitech, Emerson — legacy hardware, no AI anomaly detection, 4× more expensive.',
                    ],
                ],
            ],
            [
                'startup_name'  => 'Nafsi',
                'founder_name'  => 'Amira Hassan',
                'founder_email' => 'amira@nafsi.health',
                'country_code'  => 'EG',
                'stage'         => 'pre_seed',
                'sector'        => 'healthtech',
                'status'        => 'submitted',
                'submitted_at'  => now()->subDays(2),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'On-demand Arabic mental health platform connecting Egyptians to licensed therapists via text and video.',
                        'website' => 'https://nafsi.health',
                        'business_type' => 'b2c',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'yes',
                    ],
                    'business' => [
                        'business_model' => 'marketplace',
                        'target_customers' => 'Arabic-speaking adults 18–40, urban Egypt + diaspora',
                        'founding_year' => 2024,
                        'business_model_summary' => 'Session fees (EGP 350/session); therapist takes 70%. B2B corporate wellness pilot at 2 companies.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => 'EGP 62,000 (~$1,250)',
                        'received_funding' => 'no',
                        'funding_needs' => '$200K pre-seed for therapist supply-side expansion and iOS app',
                    ],
                    'team' => [
                        'co_founder_count' => 2,
                        'team_member_count' => 4,
                        'application_reason' => 'Need mentorship on healthcare compliance in MENA and scaling a two-sided marketplace.',
                    ],
                    'narrative' => [
                        'problem' => '92% of Egyptians with mental health conditions never receive treatment. Stigma and cost are the top barriers.',
                        'solution' => 'Nafsi removes stigma with anonymous text-first sessions and reduces cost via high therapist utilization.',
                        'market' => '$1.1B MENA digital mental health market by 2028.',
                        'traction' => '380 paying users, 18 therapists on platform, 4.7-star avg session rating.',
                        'competition' => 'BetterHelp (no Arabic), Shezlong (Egypt, limited therapist supply). Nafsi differentiated by AI session summaries and employer EAP bundle.',
                    ],
                ],
            ],
            [
                'startup_name'  => 'AgriSense',
                'founder_name'  => 'Kerem Doğan',
                'founder_email' => 'kerem@agrisense.ag',
                'country_code'  => 'TR',
                'stage'         => 'mvp',
                'sector'        => 'agritech',
                'status'        => 'submitted',
                'submitted_at'  => now()->subDay(),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'Satellite + soil sensor fusion platform that predicts crop disease 10 days in advance for small-holder farmers in Anatolia.',
                        'website' => 'https://agrisense.ag',
                        'business_type' => 'b2b',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'yes',
                    ],
                    'business' => [
                        'business_model' => 'saas',
                        'target_customers' => 'Agricultural cooperatives (3,000–50,000 decare farms), crop insurance companies',
                        'founding_year' => 2023,
                        'business_model_summary' => '₺120/decare/season subscription. Insurance companies pay for risk-scoring API (₺18/policy).',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => '₺95,000 (~$3K)',
                        'received_funding' => 'yes',
                        'funding_history' => 'KOSGEB Innovation Support ₺500K + university IP licence',
                        'funding_needs' => '$800K to add drone scouting layer and expand to Balkans',
                    ],
                    'team' => [
                        'co_founder_count' => 3,
                        'team_member_count' => 8,
                        'application_reason' => 'Business model refinement for insurance channel and access to agritech-focused investors.',
                    ],
                    'narrative' => [
                        'problem' => 'Turkish small-holder farmers lose 18–25% of yield annually to late blight and other preventable diseases. Early warning services require expensive agronomist visits.',
                        'solution' => 'AgriSense fuses Sentinel-2 satellite NDVI data with in-field IoT sensors; Gemini interprets anomaly patterns and pushes WhatsApp alerts in Turkish.',
                        'market' => '$4.2B precision agriculture market Turkey + Balkans.',
                        'traction' => '11 cooperatives covering 280,000 decares, 2 insurance API pilots, 22% average yield improvement reported.',
                        'competition' => 'Trimble, John Deere — designed for large Western farms. No local competitor with satellite + IoT + LLM layer.',
                    ],
                ],
            ],
            [
                'startup_name'  => 'Tabib AI',
                'founder_name'  => 'Dr. Sara Al-Mutairi',
                'founder_email' => 'sara@tabib.ai',
                'country_code'  => 'KW',
                'stage'         => 'seed',
                'sector'        => 'healthtech',
                'status'        => 'screened',
                'submitted_at'  => now()->subDays(7),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'AI-powered clinical decision support tool that assists Gulf-region GPs with differential diagnosis in Arabic.',
                        'website' => 'https://tabib.ai',
                        'business_type' => 'b2b',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'yes',
                    ],
                    'business' => [
                        'business_model' => 'saas',
                        'target_customers' => 'Private clinics, polyclinics and hospital outpatient departments in GCC',
                        'founding_year' => 2023,
                        'business_model_summary' => 'KWD 180/month per physician seat (~$585). Group clinic discount at 15+ seats.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => 'KWD 3,600 (~$11.7K)',
                        'received_funding' => 'yes',
                        'funding_history' => '$300K seed from Kuwait angel syndicate (Q1 2025)',
                        'funding_needs' => '$1.5M Series A for Saudi MOH pilot and FDA 510(k) pathway',
                    ],
                    'team' => [
                        'co_founder_count' => 2,
                        'team_member_count' => 9,
                        'application_reason' => 'Regulatory strategy for Gulf health authorities and warm intros to hospital group procurement.',
                    ],
                    'narrative' => [
                        'problem' => 'Gulf GPs spend 40% of consultation time on documentation and face high diagnostic error rates due to patient-load pressure and limited specialist access.',
                        'solution' => 'Tabib AI listens to the Arabic consultation, surfaces ranked differentials with evidence citations, and auto-drafts the SOAP note — physician reviews in one click.',
                        'market' => '~32,000 private GPs in GCC. $4.8B Gulf digital health market by 2027.',
                        'traction' => '22 clinics live, 64 physicians, 18,000 consultations assisted, 31% reduction in average consultation time (pilot data).',
                        'competition' => 'Nuance DAX, Suki (English-only, US-focused). No Arabic-first clinical AI with Gulf drug formularies and ICD-11 localisation.',
                    ],
                ],
            ],
            [
                'startup_name'  => 'Qissat',
                'founder_name'  => 'Nour Khalifa',
                'founder_email' => 'nour@qissat.io',
                'country_code'  => 'AE',
                'stage'         => 'pre_seed',
                'sector'        => 'edtech',
                'status'        => 'needs_info',
                'submitted_at'  => now()->subDays(6),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'Generative-AI Arabic storytelling platform that creates personalised bedtime stories for children aged 3–9.',
                        'website' => 'https://qissat.io',
                        'business_type' => 'b2c',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'no',
                    ],
                    'business' => [
                        'business_model' => 'saas',
                        'target_customers' => 'Arab parents globally (diaspora + GCC + Levant)',
                        'founding_year' => 2025,
                        'business_model_summary' => 'Freemium: 3 stories/month free; AED 29/month unlimited + voice narration.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => 'AED 4,200 (~$1,150)',
                        'received_funding' => 'no',
                        'funding_needs' => '$180K pre-seed for illustrator AI layer and Android app',
                    ],
                    'team' => [
                        'co_founder_count' => 1,
                        'team_member_count' => 2,
                        'application_reason' => 'Product-market fit coaching and introductions to children\'s media publishers for content licensing.',
                    ],
                    'narrative' => [
                        'problem' => 'Arabic children\'s digital content is scarce. Most e-books are translated from English, stripping cultural context. Parents struggle to maintain Arabic at home in diaspora settings.',
                        'solution' => 'Qissat uses Gemini to generate culturally-rich, personalised stories using the child\'s name and preferences; parents rate each story and the model adapts.',
                        'market' => '370M Arabic speakers worldwide; $1.8B Arabic digital education content gap by 2026.',
                        'traction' => '2,800 registered users, 210 paid subscribers, 4.8-star App Store rating, featured by Hamdan Innovation in Dubai.',
                        'competition' => 'Storytime AI (English), Bedtimestory.ai (no Arabic). First Arabic generative children\'s story platform at scale.',
                    ],
                ],
            ],
            [
                'startup_name'  => 'FleetIQ',
                'founder_name'  => 'Yusuf Özdemir',
                'founder_email' => 'yusuf@fleetiq.io',
                'country_code'  => 'TR',
                'stage'         => 'mvp',
                'sector'        => 'logistics',
                'status'        => 'submitted',
                'submitted_at'  => now()->subDays(3),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'Predictive maintenance SaaS for SME trucking fleets in Turkey, using OBD-II telemetry and Gemini to forecast breakdowns 7 days ahead.',
                        'website' => 'https://fleetiq.io',
                        'business_type' => 'b2b',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'yes',
                    ],
                    'business' => [
                        'business_model' => 'saas',
                        'target_customers' => 'SME trucking companies owning 5–50 vehicles in Turkey and Balkans',
                        'founding_year' => 2024,
                        'business_model_summary' => '₺350/vehicle/month + one-time OBD dongle (₺400). Annual prepay 10% discount. Current MRR ₺210K.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => '₺210,000 (~$6.5K)',
                        'received_funding' => 'yes',
                        'funding_history' => 'TUBITAK 1507 SME grant ₺750K (2024)',
                        'funding_needs' => '$600K to expand sensor compatibility and launch in Romania and Bulgaria',
                    ],
                    'team' => [
                        'co_founder_count' => 2,
                        'team_member_count' => 7,
                        'application_reason' => 'Scale GTM via fleet association partnerships and fleet insurance channel.',
                    ],
                    'narrative' => [
                        'problem' => 'Unplanned truck breakdowns cost Turkish SME fleet operators an average of ₺28,000 per incident in towing, lost revenue, and emergency repairs. 70% have no telematics.',
                        'solution' => 'FleetIQ plugs an OBD-II dongle into any truck; Gemini analyses engine telemetry patterns and sends WhatsApp alerts 7 days before likely failure with recommended action.',
                        'market' => '750K commercial vehicles in Turkey. $820M telematics market, 18% CAGR.',
                        'traction' => '600 vehicles on platform across 48 fleet operators, 14 breakdowns prevented in 6 months (verified), NPS 74.',
                        'competition' => 'Samsara, Geotab (expensive, English-only, large fleets). No Turkish-language AI predictive maintenance tool for small fleets.',
                    ],
                ],
            ],
            [
                'startup_name'  => 'PayLink MENA',
                'founder_name'  => 'Ahmad Barakat',
                'founder_email' => 'ahmad@paylink.me',
                'country_code'  => 'JO',
                'stage'         => 'seed',
                'sector'        => 'fintech',
                'status'        => 'screened',
                'submitted_at'  => now()->subDays(8),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'No-code payment link and checkout infrastructure for MENA merchants who lack developer resources.',
                        'website' => 'https://paylink.me',
                        'business_type' => 'b2b',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'yes',
                    ],
                    'business' => [
                        'business_model' => 'marketplace',
                        'target_customers' => 'SME merchants, freelancers, and D2C brands in Jordan, Lebanon, Egypt, Iraq',
                        'founding_year' => 2023,
                        'business_model_summary' => '2.2% + $0.30 per transaction. Premium plan $49/month removes per-transaction fee above $5K GMV.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => '$22,000 net revenue ($1.1M GMV at 2%)',
                        'received_funding' => 'yes',
                        'funding_history' => '$400K pre-seed (Wamda Capital + 2 angels, Q3 2024)',
                        'funding_needs' => '$2M Series A for Saudi SAMA licence and buy-now-pay-later feature',
                    ],
                    'team' => [
                        'co_founder_count' => 3,
                        'team_member_count' => 14,
                        'application_reason' => 'Saudi market regulatory guidance and introductions to Series A fintech investors in the Gulf.',
                    ],
                    'narrative' => [
                        'problem' => '80% of MENA merchants still collect payments via WhatsApp screenshots and bank transfers. Integration-first solutions like Stripe require a developer and 3+ weeks.',
                        'solution' => 'PayLink generates a branded checkout page in 60 seconds with no code; supports local methods (CliQ, Fawry, OmanNet); Gemini auto-classifies disputes and drafts merchant responses.',
                        'market' => 'MENA digital payments $5.7T by 2027; SME segment under-served by existing providers.',
                        'traction' => '3,200 active merchants, $1.1M monthly GMV, 340% YoY growth, 2.1% monthly churn.',
                        'competition' => 'Tap Payments (developer-first), Moyasar (Saudi-only), Stripe (not licensed in most MENA). PayLink is the no-code layer on top of local acquirers.',
                    ],
                ],
            ],
            [
                'startup_name'  => 'SkillBridge MENA',
                'founder_name'  => 'Omar Khalil',
                'founder_email' => 'omar@skillbridge.io',
                'country_code'  => 'JO',
                'stage'         => 'pre_seed',
                'sector'        => 'hrtech',
                'status'        => 'submitted',
                'submitted_at'  => now()->subHours(6),
                'form_data'     => [
                    'profile_version' => 2,
                    'basic' => [
                        'short_description' => 'AI matching platform connecting MENA companies to vetted freelance tech talent within 48 hours.',
                        'website' => 'https://skillbridge.io',
                        'business_type' => 'b2b',
                        'operating_status' => 'active',
                        'legally_incorporated' => 'yes',
                    ],
                    'business' => [
                        'business_model' => 'marketplace',
                        'target_customers' => 'Tech startups and SMEs in Jordan, Lebanon, Egypt needing senior developers without full-time commitment',
                        'founding_year' => 2024,
                        'business_model_summary' => '18% platform fee on contracts. Average contract $3,200. Monthly GMV growing 40% MoM.',
                    ],
                    'funding' => [
                        'revenue_generating' => 'yes',
                        'monthly_revenue' => '$6,800 GMV → $1,224 net',
                        'received_funding' => 'no',
                        'funding_needs' => '$350K pre-seed for supply-side (talent vetting) and KSA launch',
                    ],
                    'team' => [
                        'co_founder_count' => 2,
                        'team_member_count' => 5,
                        'application_reason' => 'GTM coaching for enterprise sales cycle and investor warm intros in Gulf.',
                    ],
                    'narrative' => [
                        'problem' => 'MENA companies fill senior tech roles in 60–90 days. 72% of projects stall while waiting for talent. Upwork profiles are unverified and timezone-mismatched.',
                        'solution' => 'SkillBridge vets candidates via live coding challenges + AI behavioural screening; Gemini matches talent to projects based on stack, availability and culture fit.',
                        'market' => '$780M MENA freelance tech market, $8B regional IT staffing.',
                        'traction' => '210 vetted freelancers, 34 companies placed, $38K cumulative GMV in 4 months, NPS 71.',
                        'competition' => 'Upwork (unvetted, US-centric), Toptal (expensive, slow). No MENA-native quality-gated marketplace.',
                    ],
                ],
            ],
        ];

        foreach ($apps as $app) {
            Application::firstOrCreate(
                [
                    'program_id'   => $program->id,
                    'startup_name' => $app['startup_name'],
                ],
                array_merge(
                    \Illuminate\Support\Arr::except($app, ['startup_name']),
                    ['program_id' => $program->id, 'startup_name' => $app['startup_name']]
                )
            );
        }
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
