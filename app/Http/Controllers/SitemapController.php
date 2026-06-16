<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $now = now()->toAtomString();

        $urls = collect(config('seo.sitemap_static', []))
            ->map(fn (array $entry) => [
                'loc' => $baseUrl.$entry['path'],
                'lastmod' => $now,
                'changefreq' => $entry['changefreq'] ?? 'monthly',
                'priority' => $entry['priority'] ?? '0.5',
            ]);

        Program::query()
            ->where('status', 'open')
            ->whereNotNull('slug')
            ->select(['slug', 'updated_at'])
            ->each(function (Program $program) use ($urls, $baseUrl) {
                $urls->push([
                    'loc' => $baseUrl.'/apply/'.$program->slug,
                    'lastmod' => $program->updated_at?->toAtomString() ?? now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ]);
            });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
