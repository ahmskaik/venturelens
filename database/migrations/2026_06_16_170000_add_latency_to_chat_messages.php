<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedInteger('retrieval_ms')->nullable()->after('completion_tokens');
            $table->unsignedInteger('generation_ms')->nullable()->after('retrieval_ms');
            $table->unsignedInteger('latency_ms')->nullable()->after('generation_ms');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['retrieval_ms', 'generation_ms', 'latency_ms']);
        });
    }
};
