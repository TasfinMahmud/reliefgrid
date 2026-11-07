<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->unsignedSmallInteger('household_size_at_activation');
            $table->timestamps();

            $table->unique(['assistance_cycle_id', 'household_id', 'item_id'], 'entitlements_cycle_household_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
    }
};
