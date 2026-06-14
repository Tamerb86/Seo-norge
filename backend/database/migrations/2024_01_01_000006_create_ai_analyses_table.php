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
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->enum('type', [
                'content_analysis',
                'keyword_suggestion',
                'content_generation',
                'competitor_analysis'
            ]);
            $table->string('input_url')->nullable();
            $table->text('input_content')->nullable();
            $table->string('input_keyword')->nullable();
            $table->json('result');
            $table->integer('tokens_used')->default(0);
            $table->string('model_used')->default('gpt-4');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Index for usage tracking
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
