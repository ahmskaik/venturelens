<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubric extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'criteria',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'criteria' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public static function defaultCriteria(): array
    {
        return [
            [
                'name' => 'Team',
                'weight' => 25,
                'description' => 'Founder experience, complementary skills, commitment',
                'scoring_guide' => '1=no relevant experience, 10=exceptional proven team',
            ],
            [
                'name' => 'Market Opportunity',
                'weight' => 25,
                'description' => 'Problem significance, market size, timing',
                'scoring_guide' => '1=unclear problem, 10=large validated opportunity',
            ],
            [
                'name' => 'Traction',
                'weight' => 25,
                'description' => 'Revenue, users, pilots, partnerships',
                'scoring_guide' => '1=no validation, 10=strong measurable traction',
            ],
            [
                'name' => 'Innovation',
                'weight' => 25,
                'description' => 'Differentiation, defensibility, scalability',
                'scoring_guide' => '1=commodity idea, 10=highly differentiated',
            ],
        ];
    }
}
