<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Fallback meta for crawlers before Inertia hydrates --}}
        <meta name="description" content="{{ config('seo.description') }}">
        <meta name="keywords" content="{{ implode(', ', config('seo.keywords', [])) }}">
        <meta name="author" content="{{ config('seo.author') }}">
        <meta name="robots" content="index, follow">
        <meta name="theme-color" content="{{ config('seo.theme_color') }}">
        <meta name="application-name" content="{{ config('seo.site_name') }}">
        <meta name="format-detection" content="telephone=no">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('seo.site_name') }}">
        <meta property="og:title" content="{{ config('seo.site_name') }} — {{ config('seo.tagline') }}">
        <meta property="og:description" content="{{ config('seo.description') }}">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:image" content="{{ rtrim(config('app.url'), '/') }}{{ config('seo.og_image') }}">
        <meta property="og:locale" content="{{ str_replace('_', '-', config('seo.locale', 'en')) }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:site" content="{{ config('seo.twitter_handle') }}">
        <meta name="twitter:title" content="{{ config('seo.site_name') }}">
        <meta name="twitter:description" content="{{ config('seo.description') }}">
        <meta name="twitter:image" content="{{ rtrim(config('app.url'), '/') }}{{ config('seo.og_image') }}">

        <link rel="canonical" href="{{ config('app.url') }}">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="manifest" href="/site.webmanifest">

        <title inertia>{{ config('app.name', 'VentureLens') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased bg-slate-50 text-slate-900">
        @inertia
    </body>
</html>
