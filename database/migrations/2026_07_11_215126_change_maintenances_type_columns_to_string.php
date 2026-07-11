<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE maintenances MODIFY type VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE maintenances MODIFY sub_type VARCHAR(50) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE maintenances MODIFY type ENUM(
            'oil_change', 'tire_change', 'brake_service', 'engine_repair', 'body_repair', 'electrical', 'cleaning', 'other'
        ) NOT NULL");
        DB::statement("ALTER TABLE maintenances MODIFY sub_type ENUM(
            'oil_change', 'tire_change', 'brake_service', 'filter_change', 'battery', 'timing_belt', 'general_service', 'other'
        ) NULL");
    }
};
