<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usage_records')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE usage_records MODIFY COLUMN type ENUM('screening', 'report', 'email', 'chat') NOT NULL"
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('usage_records') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('usage_records')->where('type', 'chat')->delete();

        DB::statement(
            "ALTER TABLE usage_records MODIFY COLUMN type ENUM('screening', 'report', 'email') NOT NULL"
        );
    }
};
