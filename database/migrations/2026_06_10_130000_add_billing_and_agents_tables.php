<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->index()->after('website');
            $table->string('pm_type')->nullable()->after('stripe_id');
            $table->string('pm_last_four', 4)->nullable()->after('pm_type');
            $table->timestamp('trial_ends_at')->nullable()->after('pm_last_four');
        });

        if (Schema::hasColumn('organizations', 'stripe_customer_id')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropColumn('stripe_customer_id');
            });
        }

        Schema::create('revenue_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_invoice_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('usd');
            $table->enum('plan', ['cohort', 'starter', 'pro']);
            $table->enum('revenue_type', ['arms_length', 'related_party'])->default('arms_length');
            $table->enum('classification_source', ['rule', 'checkout', 'manual', 'gemini'])->default('rule');
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });

        Schema::create('business_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('enabled')->default(true);
            $table->unsignedTinyInteger('autonomy_level')->default(3);
            $table->unsignedInteger('daily_action_cap')->default(50);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('question');
            $table->enum('status', ['open', 'answered', 'escalated'])->default('open');
            $table->text('ai_response')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->unsignedTinyInteger('autonomy_level')->nullable();
            $table->timestamps();
        });

        Schema::create('growth_outreach_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('target_organization');
            $table->string('target_contact_email')->nullable();
            $table->string('target_country', 2)->nullable();
            $table->string('channel')->default('email');
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['draft', 'queued', 'sent', 'skipped'])->default('draft');
            $table->unsignedTinyInteger('autonomy_level')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_outreach_drafts');
        Schema::dropIfExists('support_requests');
        Schema::dropIfExists('business_agents');
        Schema::dropIfExists('revenue_charges');

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at']);
            $table->string('stripe_customer_id')->nullable();
        });
    }
};
