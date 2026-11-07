<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('block_id')->constrained()->restrictOnDelete();
            $table->string('head_name', 120);
            $table->string('head_sex', 10)->nullable();
            $table->unsignedSmallInteger('head_age')->nullable();
            $table->string('phone', 32)->nullable();
            $table->unsignedSmallInteger('household_size');
            $table->string('shelter_number', 40)->nullable();
            $table->text('vulnerability_notes')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->string('consent_version', 30)->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('deactivation_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['block_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
