<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_type', ['incubator', 'founder'])->default('incubator')->after('email');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('founder_user_id')->nullable()->after('program_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('founder_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('default_country_code', 2)->default('US');
            $table->string('phone')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->text('bio')->nullable();
            $table->json('project_defaults')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('founder_profiles');

        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('founder_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
