<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->char('country_code', 2)->default('US');
            $table->string('website')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->enum('plan', ['free', 'cohort', 'starter', 'pro'])->default('free');
            $table->unsignedInteger('screenings_quota')->default(5);
            $table->unsignedInteger('screenings_used')->default(0);
            $table->timestamps();
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'manager', 'reviewer'])->default('manager');
            $table->timestamps();
            $table->primary(['organization_id', 'user_id']);
        });

        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('criteria');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->unsignedInteger('max_applications')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'archived'])->default('draft');
            $table->foreignId('rubric_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('startup_name');
            $table->string('founder_name');
            $table->string('founder_email');
            $table->char('country_code', 2)->default('US');
            $table->string('stage')->default('idea');
            $table->string('sector')->nullable();
            $table->json('form_data')->nullable();
            $table->enum('status', [
                'draft', 'submitted', 'processing', 'screened', 'needs_info',
                'shortlisted', 'accepted', 'rejected', 'waitlisted',
            ])->default('draft');
            $table->decimal('ai_overall_score', 5, 2)->nullable();
            $table->decimal('manual_overall_score', 5, 2)->nullable();
            $table->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decision_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status_token', 64)->unique()->nullable();
            $table->timestamps();
        });

        Schema::create('application_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['pitch_deck', 'supplementary'])->default('pitch_deck');
            $table->string('storage_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->longText('extracted_text')->nullable();
            $table->timestamps();
        });

        Schema::create('screening_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('model');
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->json('criterion_scores')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->json('risk_flags')->nullable();
            $table->text('summary')->nullable();
            $table->string('recommendation')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->timestamps();
        });

        Schema::create('agent_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('agent_name')->nullable();
            $table->string('step');
            $table->string('decision')->nullable();
            $table->string('action_taken')->nullable();
            $table->unsignedTinyInteger('autonomy_level')->default(3);
            $table->decimal('confidence', 4, 3)->nullable();
            $table->unsignedInteger('human_minutes_saved')->nullable();
            $table->enum('status', ['started', 'completed', 'failed'])->default('started');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['screening', 'report', 'email']);
            $table->unsignedInteger('gemini_calls')->default(0);
            $table->unsignedInteger('tokens')->default(0);
            $table->date('recorded_at');
            $table->timestamps();

            $table->unique(['organization_id', 'type', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('agent_executions');
        Schema::dropIfExists('screening_results');
        Schema::dropIfExists('application_files');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('rubrics');
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
