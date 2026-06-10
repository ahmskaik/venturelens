<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\RevenueCharge;
use App\Models\User;
use Illuminate\Support\Str;

class RevenueClassifier
{
    public function classify(Organization $organization, ?User $user = null): string
    {
        if ($this->matchesRelatedPartySlug($organization->slug)) {
            return 'related_party';
        }

        if ($organization->website && $this->domainMatches($organization->website)) {
            return 'related_party';
        }

        if ($user && $this->emailDomainMatches($user->email)) {
            return 'related_party';
        }

        $owner = $organization->users()->wherePivot('role', 'owner')->first();
        if ($owner && $this->emailDomainMatches($owner->email)) {
            return 'related_party';
        }

        return 'arms_length';
    }

    public function emailDomainMatches(string $email): bool
    {
        $domain = Str::lower(Str::after($email, '@'));

        return in_array($domain, $this->relatedDomains(), true);
    }

    private function matchesRelatedPartySlug(string $slug): bool
    {
        $slugs = config('venturelens.related_party.organization_slugs', []);

        foreach ($slugs as $related) {
            if ($slug === $related || str_starts_with($slug, $related.'-')) {
                return true;
            }
        }

        return false;
    }

    private function domainMatches(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? $url;
        $host = Str::lower(preg_replace('/^www\./', '', $host));

        return in_array($host, $this->relatedDomains(), true);
    }

    /**
     * @return list<string>
     */
    private function relatedDomains(): array
    {
        return array_map('strtolower', config('venturelens.related_party.email_domains', []));
    }
}
