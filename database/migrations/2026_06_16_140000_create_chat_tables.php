<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->json('sources')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->timestamps();
        });

        Schema::table('support_requests', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->json('sources')->nullable()->after('ai_response');
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn('sources');
        });

        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');
    }
};
