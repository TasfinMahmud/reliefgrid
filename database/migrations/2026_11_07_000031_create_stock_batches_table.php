<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 60);
            $table->date('expiry_date')->nullable();
            $table->string('supplier', 120)->nullable();
            $table->decimal('received_quantity', 12, 2);
            $table->date('received_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('origin_batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'item_id', 'batch_number']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
