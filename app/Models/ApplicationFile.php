<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationFile extends Model
{
    protected $fillable = [
        'application_id',
        'type',
        'storage_path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'extracted_text',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
