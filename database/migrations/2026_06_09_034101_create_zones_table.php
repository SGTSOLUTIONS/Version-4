<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('corp_id')
                ->constrained('corporations')
                ->onDelete('cascade');

            $table->string('zone_name');
            $table->string('zone_code')->unique();

            $table->text('description')->nullable();

            $table->string('contact_person')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();

            $table->string('address')->nullable();
            $table->string('pincode', 10)->nullable();

            $table->integer('total_wards')->default(0);

            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
