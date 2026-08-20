<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The global 'counters' Settings group is fully superseded by the new
     * per-agency agency_document_counters table (seeded in the two prior
     * migrations) — these rows are now dead data.
     */
    public function up(): void
    {
        DB::table('settings')->where('group', 'counters')->delete();
    }

    public function down(): void
    {
        // Not meaningfully reversible — the old global counters no longer
        // drive anything even if restored.
    }
};
