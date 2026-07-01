<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();

            // Foreign Key
            $table->foreignId('zone_id')
                  ->constrained('zones')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            // Ward Details
            $table->string('ward_no', 50)->unique();

            // Drone Image
            $table->string('drone_image')->nullable();

            // Map Extents
            $table->decimal('extent_left', 12, 6)->nullable();
            $table->decimal('extent_right', 12, 6)->nullable();
            $table->decimal('extent_top', 12, 6)->nullable();
            $table->decimal('extent_bottom', 12, 6)->nullable();

            // GeoJSON / WKT Boundary
            $table->longText('boundary')->nullable();

            // Optional Zone Name
            $table->string('zone')->nullable();

            // Contact Information
            $table->string('contact_person')->nullable();
            $table->string('designation')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive'])
                  ->default('active');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('zone_id');
            $table->index('ward_no');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
