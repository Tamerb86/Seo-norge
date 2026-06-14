<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('domain_id');
            $table->uuid('cluster_id')->nullable();

            // Planning fields
            $table->string('keyword');                 // primary target keyword
            $table->string('intent')->nullable();      // informational|commercial|transactional|local
            $table->integer('priority')->default(0);

            // Content fields
            $table->string('h1')->nullable();
            $table->string('title_tag')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('slug')->nullable();
            $table->json('secondary_keywords')->nullable();
            $table->longText('body_markdown')->nullable();
            $table->json('faq')->nullable();
            $table->json('internal_links')->nullable();
            $table->json('image_alts')->nullable();
            $table->json('schema_types')->nullable();
            $table->json('warnings')->nullable();
            $table->integer('word_count')->default(0);

            // Lifecycle
            $table->string('status')->default('draft'); // draft|scheduled|published|failed
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('published_url')->nullable();

            $table->timestamps();

            $table->foreign('domain_id')->references('id')->on('domains')->onDelete('cascade');
            $table->foreign('cluster_id')->references('id')->on('clusters')->onDelete('set null');
            $table->index(['domain_id', 'status']);
            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
