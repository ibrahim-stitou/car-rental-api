<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE claims MODIFY accident_type VARCHAR(50) NOT NULL DEFAULT 'collision'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE claims MODIFY accident_type ENUM(
            'collision', 'theft', 'vandalism', 'natural_disaster', 'fire', 'glass_damage', 'parking', 'other'
        ) NOT NULL DEFAULT 'collision'");
    }
};
