<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Map old enum values that have no direct equivalent before changing the column definition.
        DB::statement("
            UPDATE vehicles SET category = CASE category
                WHEN 'economy'  THEN 'hatchback'
                WHEN 'compact'  THEN 'sedan'
                WHEN 'midsize'  THEN 'sedan'
                WHEN 'luxury'   THEN 'sedan'
                ELSE category
            END
            WHERE category IN ('economy','compact','midsize','luxury')
        ");

        DB::statement("
            ALTER TABLE vehicles
            MODIFY COLUMN category ENUM('sedan','suv','van','truck','convertible','coupe','hatchback','minivan') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE vehicles SET category = CASE category
                WHEN 'sedan'       THEN 'compact'
                WHEN 'hatchback'   THEN 'economy'
                WHEN 'truck'       THEN 'van'
                WHEN 'convertible' THEN 'luxury'
                WHEN 'coupe'       THEN 'luxury'
                WHEN 'minivan'     THEN 'van'
                ELSE category
            END
        ");

        DB::statement("
            ALTER TABLE vehicles
            MODIFY COLUMN category ENUM('economy','compact','midsize','suv','luxury','van') NOT NULL
        ");
    }
};
