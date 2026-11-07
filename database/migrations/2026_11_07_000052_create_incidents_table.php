<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('block_id')->constrained()->restrictOnDelete();
            $table->string('hazard_type', 30)->index();
            $table->string('title', 150);
            $table->text('description');
            $table->timestamp('occurred_at');
            $table->string('status', 30)->default('reported')->index();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hazard_alert_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('evacuation_site_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('households_affected')->nullable();
            $table->unsignedInteger('people_affected')->nullable();
            $table->unsignedInteger('injuries')->nullable();
            $table->unsignedInteger('deaths')->nullable();

            $table->foreignId('investigated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('investigation_started_at')->nullable();
            $table->text('investigation_notes')->nullable();

            $table->foreignId('response_lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('response_started_at')->nullable();
            $table->text('response_notes')->nullable();

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestamps();
            $table->index(['block_id', 'status']);
        });

        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incidents');
    }
};
