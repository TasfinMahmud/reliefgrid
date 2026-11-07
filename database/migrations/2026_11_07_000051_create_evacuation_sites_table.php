<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evacuation_sites', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->foreignId('block_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('current_occupancy')->default(0);
            $table->boolean('is_open')->default(true);
            $table->text('facilities')->nullable();
            $table->string('contact_person', 120)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('evacuation_occupancy_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_site_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('previous_occupancy');
            $table->unsignedInteger('occupancy');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuation_occupancy_logs');
        Schema::dropIfExists('evacuation_sites');
    }
};
