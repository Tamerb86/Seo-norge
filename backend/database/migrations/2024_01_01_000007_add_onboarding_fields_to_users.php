<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // NOTE: full_name and company already exist (migration 000001).
            // Only genuinely new columns are added here.
            $table->string('role')->nullable()->after('company');

            // Onboarding status
            $table->boolean('onboarding_completed')->default(false)->after('role');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_completed');
            
            // Goals and experience
            $table->json('goals')->nullable()->after('onboarding_completed_at');
            $table->string('seo_experience')->nullable()->after('goals');
            
            // Checklist
            $table->boolean('checklist_dismissed')->default(false)->after('seo_experience');
            $table->timestamp('checklist_dismissed_at')->nullable()->after('checklist_dismissed');
            
            // AI usage counter
            $table->integer('ai_analyses_count')->default(0)->after('checklist_dismissed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'onboarding_completed',
                'onboarding_completed_at',
                'goals',
                'seo_experience',
                'checklist_dismissed',
                'checklist_dismissed_at',
                'ai_analyses_count',
            ]);
        });
    }
};
