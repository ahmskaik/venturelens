<?php

return [
    'uploads_disk' => env('FILESYSTEM_UPLOADS_DISK', 'local'),

    'demo' => [
        'email' => env('DEMO_USER_EMAIL', 'demo@venturelens.app'),
        'password' => env('DEMO_USER_PASSWORD', 'demo123'),
        'founder_email' => env('DEMO_FOUNDER_EMAIL', 'founder@example.com'),
        'founder_password' => env('DEMO_FOUNDER_PASSWORD', 'demo123'),
    ],

    'project_profile' => [
        'version' => 2,
        'stages' => [
            'idea' => 'Idea stage',
            'pre_seed' => 'Pre-seed',
            'seed' => 'Seed stage',
            'mvp' => 'MVP / early traction',
            'growth' => 'Growth stage',
            'scale' => 'Scale stage',
        ],
        'business_types' => [
            'b2b' => 'B2B',
            'b2c' => 'B2C',
            'b2b2c' => 'B2B2C',
            'marketplace' => 'Marketplace',
        ],
        'operating_statuses' => [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'business_models' => [
            'saas' => 'SaaS',
            'marketplace' => 'Marketplace',
            'hardware' => 'Hardware',
            'services' => 'Services',
            'subscription' => 'Subscription',
            'transaction' => 'Transaction / commission',
            'licensing' => 'Licensing',
            'other' => 'Other',
        ],
        'sectors' => [
            'agtech' => 'AgTech',
            'cleantech' => 'CleanTech / Environmental',
            'edtech' => 'EdTech',
            'fintech' => 'FinTech',
            'healthtech' => 'HealthTech',
            'hrtech' => 'HR / Future of Work',
            'impact' => 'Impact / Social',
            'mobility' => 'Mobility',
            'proptech' => 'PropTech',
            'retail' => 'Retail / E-commerce',
            'other' => 'Other',
        ],
    ],

    'plans' => [
        'free' => [
            'name' => 'Free trial',
            'quota' => 5,
            'price_label' => '$0',
        ],
        'cohort' => [
            'name' => 'Cohort package',
            'quota' => 50,
            'price_label' => '$199 one-time',
        ],
        'starter' => [
            'name' => 'Starter',
            'quota' => 200,
            'price_label' => '$299/mo',
        ],
        'pro' => [
            'name' => 'Pro',
            'quota' => 1000,
            'price_label' => '$799/mo',
        ],
    ],

    'stripe' => [
        'prices' => [
            'cohort' => env('STRIPE_PRICE_COHORT'),
            'starter' => env('STRIPE_PRICE_STARTER'),
            'pro' => env('STRIPE_PRICE_PRO'),
        ],
    ],

    'related_party' => [
        'email_domains' => array_filter(explode(',', env('RELATED_PARTY_DOMAINS', 'gohorto.com,bina.org.tr,bina.com.tr'))),
        'organization_slugs' => array_filter(explode(',', env('RELATED_PARTY_ORG_SLUGS', 'demo-incubator,bina,gohorto'))),
    ],

    'agents' => [
        'growth' => [
            'daily_cap' => (int) env('GROWTH_AGENT_DAILY_CAP', 5),
        ],
        'support' => [
            'auto_reply_confidence' => (float) env('SUPPORT_AGENT_AUTO_CONFIDENCE', 0.85),
        ],
    ],

    'impact' => [
        'manual_review_minutes_per_app' => (int) env('IMPACT_MANUAL_REVIEW_MINUTES', 45),
        'avg_jobs_per_startup' => (float) env('IMPACT_AVG_JOBS_PER_STARTUP', 3),
        'testimonials' => [
            [
                'name' => 'Mustafa Sagezli',
                'role' => 'General Manager, BINA Program',
                'quote' => 'Across BINA programs, we needed fair, fast screening without burning out our evaluation committee. VentureLens gave us structured AI assessments our team can trust — founders get answers in days, not weeks.',
                'image' => '/images/testimonials/mustafa-sagezli.png',
                'url' => null,
            ],
            [
                'name' => 'Dr. Yavuz Selim Silay',
                'role' => 'Managing Partner, AFROTURK',
                'quote' => 'Screening cross-border deal flow between Africa and Turkey demands consistency at scale. VentureLens helps our team evaluate more ventures with the same rigor — without adding headcount.',
                'image' => '/images/testimonials/yavuz-selim-silay.png',
                'url' => null,
            ],
            [
                'name' => 'Yannick Du Pont',
                'role' => 'Chief Executive Officer, GlocalShift Foundation',
                'quote' => 'For a foundation supporting founders across multiple markets, transparent AI screening is a force multiplier. VentureLens lets us move faster while keeping every applicant on a level playing field.',
                'image' => '/images/testimonials/yannick-du-pont.png',
                'url' => null,
            ],
            [
                'name' => 'Mousa Samar',
                'role' => 'OASIS Accelerator',
                'quote' => 'Our cohort intake used to bottleneck on manual reads. With VentureLens, reviewers start from structured Gemini scores and focus on the conversations that matter — founders notice the difference.',
                'image' => '/images/testimonials/mousa-samar.png',
                'url' => null,
            ],
        ],
        'scorecard_floors' => [
            'arms_length_paying_customers' => 3,
            'arms_length_revenue_usd' => 600,
            'applications_screened' => 100,
            'pct_decisions_by_ai' => 50,
        ],
    ],
];