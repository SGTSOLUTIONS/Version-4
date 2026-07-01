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
        Schema::create('corporations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();

            // Location Details
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('pincode')->nullable();
            $table->enum('type', ['Municipal Corporation', 'Municipality', 'Town Panchayat', 'Nagar Nigam', 'City Corporation'])->default('Municipal Corporation');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('image')->nullable(); // Corporation logo/photo
            $table->geometry('boundary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporations');
    }
};
