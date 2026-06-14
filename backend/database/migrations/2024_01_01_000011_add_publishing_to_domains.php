<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            // shopify | wordpress | null
            $table->string('publish_platform')->nullable()->after('is_verified');
            // encrypted credentials (shop_domain/access_token/blog_id OR base_url/username/app_password)
            $table->text('publish_config')->nullable()->after('publish_platform');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['publish_platform', 'publish_config']);
        });
    }
};
