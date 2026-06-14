<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clusters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('content_plan_id');
            $table->uuid('domain_id');
            $table->string('pillar_title');
            $table->string('pillar_keyword')->nullable();
            $table->integer('position')->default(0); // ordering
            $table->timestamps();

            $table->foreign('content_plan_id')->references('id')->on('content_plans')->onDelete('cascade');
            $table->foreign('domain_id')->references('id')->on('domains')->onDelete('cascade');
            $table->index('domain_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clusters');
    }
};
