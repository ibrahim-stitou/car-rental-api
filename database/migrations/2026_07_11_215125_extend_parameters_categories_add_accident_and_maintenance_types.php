<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE parameters MODIFY category ENUM(
            'insurance_type', 'insurance_company', 'inspection_center', 'expense_category',
            'accident_type', 'maintenance_type', 'maintenance_sub_type'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE parameters MODIFY category ENUM(
            'insurance_type', 'insurance_company', 'inspection_center', 'expense_category'
        ) NOT NULL");
    }
};
