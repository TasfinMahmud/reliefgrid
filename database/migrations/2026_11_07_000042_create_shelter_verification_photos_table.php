<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shelter_verification_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelter_repair_request_id')->constrained('shelter_repair_requests')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('caption')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_m', 8, 1)->nullable();
            $table->string('geo_source', 10); // exif | device
            $table->timestamp('captured_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Optional PostGIS support: a geography(Point,4326) column + spatial index, kept in sync by the service.
        // Skipped silently on SQLite (tests) or on PostgreSQL without the postgis extension.
        if (DB::getDriverName() === 'pgsql') {
            $hasPostgis = DB::selectOne("select 1 as ok from pg_extension where extname = 'postgis'");
            if ($hasPostgis) {
                DB::statement('ALTER TABLE shelter_verification_photos ADD COLUMN geom geography(Point,4326)');
                DB::statement('CREATE INDEX shelter_verification_photos_geom_idx ON shelter_verification_photos USING GIST (geom)');
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shelter_verification_photos');
    }
};
