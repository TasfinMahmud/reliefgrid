<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazard_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('hazard_type', 30)->index();
            $table->string('severity', 20)->index();
            $table->string('title', 150);
            $table->text('message');
            $table->text('instructions')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('block_hazard_alert', function (Blueprint $table) {
            $table->foreignId('hazard_alert_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->primary(['hazard_alert_id', 'block_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_hazard_alert');
        Schema::dropIfExists('hazard_alerts');
    }
};
