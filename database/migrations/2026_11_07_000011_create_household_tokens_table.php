<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('token_uid', 40)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invalidated_at')->nullable();
            $table->foreignId('invalidated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invalidation_reason', 255)->nullable();
            $table->foreignId('replaces_token_id')->nullable()->constrained('household_tokens')->nullOnDelete();
            $table->timestamps();

            $table->index(['household_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_tokens');
    }
};
