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
        Schema::create('keywords', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('domain_id');
            $table->string('keyword')->index();
            $table->integer('search_volume')->nullable();
            $table->integer('difficulty')->nullable(); // 0-100
            $table->decimal('cpc', 8, 2)->nullable(); // Cost per click in NOK
            $table->enum('search_intent', ['informational', 'navigational', 'transactional', 'commercial'])->nullable();
            $table->enum('language', ['nb', 'nn', 'en'])->default('nb'); // Bokmål, Nynorsk, English
            $table->timestamps();

            $table->foreign('domain_id')
                ->references('id')
                ->on('domains')
                ->onDelete('cascade');

            $table->unique(['domain_id', 'keyword']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
