<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'app' => [
                'name' => config('app.name'),
            ],
            'seo' => [
                'siteName' => config('seo.site_name'),
                'tagline' => config('seo.tagline'),
                'description' => config('seo.description'),
                'keywords' => config('seo.keywords'),
                'author' => config('seo.author'),
                'locale' => config('seo.locale'),
                'appUrl' => config('app.url'),
                'ogImage' => config('seo.og_image'),
                'ogImageWidth' => config('seo.og_image_width'),
                'ogImageHeight' => config('seo.og_image_height'),
                'themeColor' => config('seo.theme_color'),
                'twitterHandle' => config('seo.twitter_handle'),
                'organization' => [
                    'contactEmail' => config('seo.organization.contact_email'),
                ],
            ],
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'email', 'account_type']),
                'home_route' => $request->user()?->homeRoute(),
                'organization' => fn () => $this->sharedOrganization($request),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }

    private function sharedOrganization(Request $request): ?array
    {
        $user = $request->user();
        if (! $user || $user->account_type === 'founder') {
            return null;
        }

        $organization = $user->primaryOrganization();
        if (! $organization) {
            return null;
        }

        return [
            'name' => $organization->name,
            'plan' => $organization->plan,
            'screenings_used' => $organization->screenings_used,
            'screenings_quota' => $organization->screenings_quota,
        ];
    }
}
