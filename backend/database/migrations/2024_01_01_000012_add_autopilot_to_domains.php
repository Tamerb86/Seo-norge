<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('autopilot_enabled')->default(false)->after('publish_config');
            $table->boolean('autopilot_auto_publish')->default(false)->after('autopilot_enabled');
            $table->integer('autopilot_daily_limit')->default(1)->after('autopilot_auto_publish');
            $table->string('autopilot_tone')->default('professional')->after('autopilot_daily_limit');
            $table->timestamp('autopilot_last_run_at')->nullable()->after('autopilot_tone');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'autopilot_enabled', 'autopilot_auto_publish',
                'autopilot_daily_limit', 'autopilot_tone', 'autopilot_last_run_at',
            ]);
        });
    }
};
