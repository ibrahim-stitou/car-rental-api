<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // These columns had DB defaults but NOT NULL — ConvertEmptyStringsToNull
        // middleware turns empty strings to null which violates the constraint.
        DB::statement("ALTER TABLE clients
            MODIFY nationality VARCHAR(100) NULL DEFAULT NULL,
            MODIFY driving_license_category VARCHAR(10) NULL DEFAULT NULL,
            MODIFY country VARCHAR(100) NULL DEFAULT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE clients
            MODIFY nationality VARCHAR(100) NOT NULL DEFAULT 'MA',
            MODIFY driving_license_category VARCHAR(10) NOT NULL DEFAULT 'B',
            MODIFY country VARCHAR(100) NOT NULL DEFAULT 'MA'
        ");
    }
};
