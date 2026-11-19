<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('prefix', 10);
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('logo')->nullable();
            $table->string('color', 7)->default('#F9C74F');
            $table->string('number_format', 50)->default('{PREFIX}{NUMBER}');
            $table->unsignedTinyInteger('number_padding')->default(3);
            $table->string('reset_period', 20)->default('daily');
            $table->json('operating_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_items');
    }
};
