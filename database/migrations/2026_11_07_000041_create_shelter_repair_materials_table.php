<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelter_repair_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelter_repair_request_id')->constrained('shelter_repair_requests')->cascadeOnDelete();
            $table->string('item_name');
            $table->string('unit', 30);
            $table->decimal('quantity_required', 12, 2);
            $table->decimal('quantity_issued', 12, 2)->default(0);
            // Last stock batch issued from (Feature 5). Plain column, no FK: Feature 12 must install without Feature 5.
            $table->unsignedBigInteger('stock_batch_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelter_repair_materials');
    }
};
