<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistance_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('eligible_household_count')->nullable();
            $table->timestamps();
        });

        // Optional block scope for a cycle (no rows = all blocks).
        Schema::create('assistance_cycle_block', function (Blueprint $table) {
            $table->foreignId('assistance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->primary(['assistance_cycle_id', 'block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_cycle_block');
        Schema::dropIfExists('assistance_cycles');
    }
};
