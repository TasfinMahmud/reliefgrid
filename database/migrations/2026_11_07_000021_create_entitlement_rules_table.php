<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlement_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('min_household_size')->default(1);
            $table->unsignedSmallInteger('max_household_size')->nullable();
            $table->decimal('base_quantity', 10, 2)->default(0);
            $table->decimal('per_member_quantity', 10, 2)->default(0);
            $table->decimal('max_quantity', 10, 2)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['assistance_cycle_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlement_rules');
    }
};
