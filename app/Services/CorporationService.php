<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CorporationService
{
    public function createCorporationTables($corporationId)
    {
        $this->createMisTable($corporationId);
        $this->createWaterTaxTable($corporationId);
        $this->createUgdTaxTable($corporationId);
        $this->createProfessionalTaxTable($corporationId);

        return true;
    }
    public function dropCorporationTables($corporationId)
    {
        $tables = [
            'mis_' . $corporationId,
            'water_tax_' . $corporationId,
            'ugd_tax_' . $corporationId,
            'professional_tax_' . $corporationId,
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        return true;
    }

    private function createMisTable($corporationId)
    {
        $tableName = "mis_" . $corporationId;

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($corporationId) {
                $table->id();

                $table->foreignId('corporation_id')
                    ->constrained('corporations')
                    ->cascadeOnDelete();

                $table->foreignId('gisid')->nullable();

                $table->string('ward_no')->nullable();
                $table->string('assessment')->nullable();
                $table->string('old_assessment')->nullable();
                $table->string('road_name')->nullable();
                $table->string('owner_name')->nullable();
                $table->string('old_door_no')->nullable();
                $table->string('new_door_no')->nullable();
                $table->string('phone_number')->nullable();

                $table->decimal('plot_area', 12, 2)->nullable();
                $table->decimal('half_year_tax', 12, 2)->nullable();
                $table->decimal('balance', 12, 2)->nullable();

                $table->enum('usage', [
                    'Residential',
                    'Commercial',
                    'Industrial',
                    'Institutional',
                    'Vacant',
                    'Agricultural',
                    'Mixed',
                    'Hospital',
                    'School',
                    'Temple',
                    'Others'
                ])->nullable();

                $table->enum('type', [
                    'Owner',
                    'Tenant',
                    'Mixed',
                    'Government',
                    'Lease',
                    'Trust',
                    'Partnership',
                    'Private Limited',
                    'Public Limited',
                    'Others'
                ])->nullable();

                $table->string('zone')->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function createWaterTaxTable($corporationId)
    {
        $tableName = "water_tax_" . $corporationId;

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($corporationId) {
                $table->id();

                $table->foreignId('corporation_id')
                    ->constrained('corporations')
                    ->cascadeOnDelete();

                $table->foreignId('gisid')->nullable();

                $table->string('ward_no')->nullable();
                $table->string('assessment')->nullable()->index();
                $table->string('road_name')->nullable();
                $table->string('watertax_no')->nullable()->index();
                $table->string('old_watertax_no')->nullable();
                $table->string('old_door_no')->nullable();
                $table->string('new_door_no')->nullable();
                $table->string('phone_number', 20)->nullable();

                $table->decimal('slab_rate', 12, 2)->nullable();
                $table->decimal('balance', 12, 2)->nullable();

                $table->enum('usage', [
                    'Residential',
                    'Commercial',
                    'Industrial',
                    'Institutional',
                    'Vacant',
                    'Others'
                ])->nullable();

                $table->string('slab_description')->nullable();

                $table->enum('DBC_type', [
                    'Owner',
                    'Tenant',
                    'Mixed',
                    'Government',
                    'Others'
                ])->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['corporation_id', 'ward_no']);
            });
        }
    }

    private function createUgdTaxTable($corporationId)
    {
        $tableName = "ugd_tax_" . $corporationId;

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($corporationId) {
                $table->id();

                $table->foreignId('corporation_id')
                    ->constrained('corporations')
                    ->cascadeOnDelete();

                $table->foreignId('gisid')->nullable();

                $table->string('ward_no')->nullable();
                $table->string('assessment')->nullable()->index();
                $table->string('road_name')->nullable();
                $table->string('ugd_no')->nullable()->index();
                $table->string('old_ugd_no')->nullable();
                $table->string('old_door_no')->nullable();
                $table->string('new_door_no')->nullable();
                $table->string('owner_name')->nullable();
                $table->string('phone_number', 20)->nullable();

                $table->decimal('slab_rate', 12, 2)->nullable();
                $table->decimal('balance', 12, 2)->nullable();

                $table->string('usage')->nullable();
                $table->string('slab_description')->nullable();
                $table->string('DBC_type')->nullable();
                $table->string('tax_year')->nullable();

                $table->decimal('ugd_tax_amount', 12, 2)->nullable();
                $table->decimal('ugd_tax_due', 12, 2)->nullable();
                $table->decimal('ugd_tax_paid', 12, 2)->nullable();

                $table->date('ugd_tax_paid_date')->nullable();
                $table->string('payment_mode')->nullable();
                $table->string('receipt_number')->nullable();
                $table->date('due_date')->nullable();

                $table->string('status')->default('Active');
                $table->text('remarks')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['corporation_id', 'ward_no']);
                $table->index('owner_name');
                $table->index('receipt_number');
            });
        }
    }

    private function createProfessionalTaxTable($corporationId)
    {
        $tableName = "professional_tax_" . $corporationId;

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) use ($corporationId) {
                $table->id();

                $table->foreignId('corporation_id')
                    ->constrained('corporations')
                    ->cascadeOnDelete();

                $table->foreignId('gisid')->nullable();

                $table->string('ward_no')->nullable();
                $table->string('assessment')->nullable()->index();
                $table->string('pt_number')->nullable()->index();
                $table->string('old_pt_number')->nullable();
                $table->string('establishment_name')->nullable();
                $table->string('owner_name')->nullable();
                $table->string('phone_number', 20)->nullable();
                $table->string('profession_type')->nullable();
                $table->integer('employee_count')->nullable();

                $table->decimal('half_year_tax', 12, 2)->nullable();
                $table->decimal('arrears', 12, 2)->default(0);
                $table->decimal('penalty', 12, 2)->default(0);
                $table->decimal('balance', 12, 2)->default(0);
                $table->decimal('paid_amount', 12, 2)->nullable();

                $table->string('payment_status')->default('Unpaid');
                $table->string('payment_mode')->nullable();
                $table->string('receipt_number')->nullable();
                $table->string('tax_period')->nullable();
                $table->date('due_date')->nullable();
                $table->date('paid_date')->nullable();
                $table->text('remarks')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['corporation_id', 'ward_no']);
            });
        }
    }
}
