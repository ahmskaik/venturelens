<?php

return [
    'uploads_disk' => env('FILESYSTEM_UPLOADS_DISK', 'local'),

    'demo' => [
        'email' => env('DEMO_USER_EMAIL', 'demo@venturelens.app'),
        'password' => env('DEMO_USER_PASSWORD', 'demo-password-change-me'),
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
                'name' => 'Sarah Chen',
                'role' => 'Program Director, Demo Incubator',
                'quote' => 'We screened our entire cohort in a weekend instead of three weeks. Founders got feedback the same day.',
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
