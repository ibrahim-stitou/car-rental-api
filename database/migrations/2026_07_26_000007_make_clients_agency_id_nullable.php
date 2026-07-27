<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * clients.agency_id is superseded by the agency_client pivot table but kept
     * (unused, nullable) for a rollback-safety period rather than dropped outright.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE clients MODIFY agency_id CHAR(36) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE clients MODIFY agency_id CHAR(36) NOT NULL');
    }
};
