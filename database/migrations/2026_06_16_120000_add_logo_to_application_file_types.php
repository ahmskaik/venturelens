<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE application_files MODIFY COLUMN type ENUM('pitch_deck', 'supplementary', 'logo') NOT NULL DEFAULT 'pitch_deck'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE application_files MODIFY COLUMN type ENUM('pitch_deck', 'supplementary') NOT NULL DEFAULT 'pitch_deck'");
    }
};
