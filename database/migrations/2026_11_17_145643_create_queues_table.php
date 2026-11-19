<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_item_id')->constrained('queue_items')->restrictOnDelete();
            $table->string('queue_number', 40);
            $table->unsignedInteger('sequence_number');
            $table->string('period_key', 20);
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('waiting');
            $table->string('counter', 50)->nullable();
            $table->dateTime('issued_at');
            $table->dateTime('called_at')->nullable();
            $table->dateTime('recalled_at')->nullable();
            $table->dateTime('skipped_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();

            // Terisi queue_item_id hanya saat status = called, NULL untuk status lain.
            // Unique index di bawah menjamin maksimal satu antrean aktif per layanan.
            $table->unsignedBigInteger('active_slot')
                ->nullable()
                ->virtualAs("CASE WHEN status = 'called' THEN queue_item_id ELSE NULL END");

            $table->timestamps();

            $table->unique(['queue_item_id', 'period_key', 'sequence_number'], 'queues_item_period_seq_unique');
            $table->unique('active_slot', 'queues_one_active_per_item_unique');
            $table->index(['queue_item_id', 'status'], 'queues_item_status_index');
            $table->index('status');
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
