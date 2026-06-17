<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('chunk_key', 191);
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->char('content_hash', 64);
            $table->json('embedding')->nullable();
            $table->unsignedSmallInteger('dimensions')->default(768);
            $table->string('embedding_model', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('embedded_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'chunk_key']);
            $table->index(['organization_id', 'program_id']);
            $table->index(['organization_id', 'source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
