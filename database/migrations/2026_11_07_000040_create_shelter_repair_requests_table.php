<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelter_repair_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->foreignId('household_id')->constrained()->restrictOnDelete();
            $table->foreignId('block_id')->constrained()->restrictOnDelete();
            $table->string('damage_type', 30);
            $table->string('severity', 20)->nullable();
            $table->text('description');
            $table->string('status', 30)->default('submitted')->index();

            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('assessment_notes')->nullable();
            $table->unsignedSmallInteger('estimated_days')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('completion_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shelter_repair_requests');
    }
};
