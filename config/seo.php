<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site identity
    |--------------------------------------------------------------------------
    */

    'site_name' => env('SEO_SITE_NAME', env('APP_NAME', 'VentureLens')),

    'tagline' => 'AI-powered startup application screening for incubators and accelerators',

    'description' => 'VentureLens helps incubators and accelerators screen startup applications in minutes with Gemini AI scoring, risk flags, committee workflows, and a full audit trail.',

    'keywords' => [
        'startup application screening',
        'incubator software',
        'accelerator application management',
        'AI startup evaluation',
        'venture screening platform',
        'cohort intake',
        'pitch deck review',
        'Gemini AI screening',
        'startup accelerator tools',
        'application scoring software',
    ],

    'author' => 'VentureLens',

    'locale' => env('APP_LOCALE', 'en'),

    'twitter_handle' => env('SEO_TWITTER_HANDLE', '@venturelens'),

    /*
    |--------------------------------------------------------------------------
    | Default social / Open Graph image (1200×630 recommended for production)
    |--------------------------------------------------------------------------
    */

    'og_image' => env('SEO_OG_IMAGE', '/images/og-default.svg'),

    'og_image_width' => 1200,

    'og_image_height' => 630,

    'theme_color' => '#7c3aed',

    /*
    |--------------------------------------------------------------------------
    | Organization (JSON-LD)
    |--------------------------------------------------------------------------
    */

    'organization' => [
        'legal_name' => 'VentureLens',
        'founding_date' => '2026',
        'contact_email' => env('SEO_CONTACT_EMAIL', 'hello@venturelens.app'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap — static public routes (apply program slugs added dynamically)
    |--------------------------------------------------------------------------
    */

    'sitemap_static' => [
        ['path' => '/', 'changefreq' => 'weekly', 'priority' => '1.0'],
        ['path' => '/impact', 'changefreq' => 'daily', 'priority' => '0.9'],
        ['path' => '/register', 'changefreq' => 'monthly', 'priority' => '0.8'],
        ['path' => '/founder/register', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ['path' => '/login', 'changefreq' => 'yearly', 'priority' => '0.5'],
    ],

];
