<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\UsageRecord;

class UsageTracker
{
    public function recordScreening(Organization $organization, int $geminiCalls, int $tokens): void
    {
        $record = UsageRecord::firstOrNew([
            'organization_id' => $organization->id,
            'type' => 'screening',
            'recorded_at' => now()->toDateString(),
        ]);

        $record->gemini_calls = ($record->gemini_calls ?? 0) + $geminiCalls;
        $record->tokens = ($record->tokens ?? 0) + $tokens;
        $record->save();
    }
}
