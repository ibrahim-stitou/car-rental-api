<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The audits table was created with INT columns for user_id and auditable_id
        // but the app uses UUIDs (strings) as primary keys — fix both columns.
        DB::statement('ALTER TABLE audits MODIFY COLUMN user_id VARCHAR(36) NULL');
        DB::statement('ALTER TABLE audits MODIFY COLUMN auditable_id VARCHAR(36) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE audits MODIFY COLUMN user_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE audits MODIFY COLUMN auditable_id BIGINT UNSIGNED NOT NULL');
    }
};