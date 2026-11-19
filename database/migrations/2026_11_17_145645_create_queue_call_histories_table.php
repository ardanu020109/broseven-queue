<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_call_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('queues')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->string('description')->nullable();
            $table->dateTime('called_at');
            $table->timestamps();

            $table->index(['queue_id', 'action']);
            $table->index('called_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_call_histories');
    }
};
