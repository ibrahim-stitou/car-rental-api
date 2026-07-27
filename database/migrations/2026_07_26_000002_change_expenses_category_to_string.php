<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE expenses MODIFY category VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE expenses MODIFY category ENUM(
            'fuel', 'maintenance', 'insurance', 'vignette', 'inspection',
            'repair', 'cleaning', 'administrative', 'salary', 'rent', 'utilities', 'other'
        ) NOT NULL");
    }
};
