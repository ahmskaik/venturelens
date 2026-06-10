<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('success_outreach_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revenue_charge_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['draft', 'sent', 'skipped'])->default('draft');
            $table->unsignedTinyInteger('autonomy_level')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('founder_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->enum('decision', ['accepted', 'rejected', 'shortlisted', 'waitlisted']);
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['draft', 'sent'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_communications');
        Schema::dropIfExists('success_outreach_drafts');
    }
};
