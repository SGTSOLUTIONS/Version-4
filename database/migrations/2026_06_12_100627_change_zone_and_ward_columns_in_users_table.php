<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn(['zone_ids', 'ward_ids']);

            $table->string('zone_id')->nullable()->after('corporation_id');
            $table->string('ward_id')->nullable()->after('zone_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn(['zone_id', 'ward_id']);

            $table->json('zone_ids')->nullable()->after('corporation_id');
            $table->json('ward_ids')->nullable()->after('zone_ids');
        });
    }
};
