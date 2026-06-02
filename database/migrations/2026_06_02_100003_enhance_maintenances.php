<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            // Sub-type for detailed categorization
            $table->enum('sub_type', [
                'oil_change',
                'tire_change',
                'brake_service',
                'filter_change',
                'battery',
                'timing_belt',
                'general_service',
                'other',
            ])->nullable()->after('type');

            // Actual cost vs estimated cost (cost = estimated, actual_cost = réel)
            $table->decimal('actual_cost', 10, 2)->nullable()->after('cost');

            // Oil change specific
            $table->integer('next_oil_change_mileage')->nullable()->after('next_service_mileage');

            // Tire change specific
            $table->string('tire_position')->nullable()->after('next_oil_change_mileage'); // front/rear/all/spare

            // Who reported
            $table->string('title')->nullable()->after('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn(['sub_type', 'actual_cost', 'next_oil_change_mileage', 'tire_position', 'title']);
        });
    }
};
