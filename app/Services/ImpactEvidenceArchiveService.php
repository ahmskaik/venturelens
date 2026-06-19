<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImpactEvidenceArchiveService
{
    private const GCS_PREFIX = 'evidence';

    /**
     * @return list<array{name: string, date: string, source: string, url: string}>
     */
    public function list(int $limit = 12): array
    {
        $byName = [];

        foreach ($this->listFromRepository() as $item) {
            $byName[$item['name']] = $item;
        }

        foreach ($this->listFromGcs() as $item) {
            $byName[$item['name']] = $item;
        }

        $archives = array_values($byName);
        usort($archives, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return array_slice($archives, 0, $limit);
    }

    public function resolveLocalPath(string $filename): ?string
    {
        if (! $this->isValidFilename($filename)) {
            return null;
        }

        $path = base_path('docs/evidence/'.$filename);

        return is_file($path) ? $path : null;
    }

    public function isValidFilename(string $filename): bool
    {
        return (bool) preg_match('/^impact-\d{8}\.json$/', $filename);
    }

    /**
     * @return list<array{name: string, date: string, source: string, url: string}>
     */
    private function listFromRepository(): array
    {
        $dir = base_path('docs/evidence');
        if (! is_dir($dir)) {
            return [];
        }

        $items = [];
        foreach (glob($dir.'/impact-*.json') ?: [] as $path) {
            $name = basename($path);
            if (! $this->isValidFilename($name)) {
                continue;
            }

            $items[] = [
                'name' => $name,
                'date' => $this->dateFromFilename($name),
                'source' => 'repository',
                'url' => route('evidence.snapshot', ['filename' => $name]),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{name: string, date: string, source: string, url: string}>
     */
    private function listFromGcs(): array
    {
        $bucket = config('filesystems.disks.gcs.bucket');
        if (! $bucket) {
            return [];
        }

        try {
            $disk = Storage::disk('gcs');
            $files = $disk->files(self::GCS_PREFIX);
            $items = [];

            foreach ($files as $path) {
                $name = basename($path);
                if (! Str::endsWith($name, '.json') || ! $this->isValidFilename($name)) {
                    continue;
                }

                $url = route('evidence.snapshot', ['filename' => $name]);

                try {
                    if (method_exists($disk, 'temporaryUrl')) {
                        $url = $disk->temporaryUrl($path, now()->addHours(1));
                    }
                } catch (\Throwable $e) {
                    Log::debug('impact_archive.temporary_url_failed', [
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                }

                $items[] = [
                    'name' => $name,
                    'date' => $this->dateFromFilename($name),
                    'source' => 'gcs',
                    'url' => $url,
                ];
            }

            return $items;
        } catch (\Throwable $e) {
            Log::warning('impact_archive.gcs_list_failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function dateFromFilename(string $filename): string
    {
        if (preg_match('/^impact-(\d{4})(\d{2})(\d{2})\.json$/', $filename, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        return '';
    }
}
