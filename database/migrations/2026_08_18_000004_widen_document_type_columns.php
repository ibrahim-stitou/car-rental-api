<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * document_type was sized for the original 7 short billing codes
     * (fa/av/dv/bc/bl/br/lld, all <= 3 chars) — "reservation" (11 chars)
     * no longer fits in the 10-char column.
     */
    public function up(): void
    {
        Schema::table('agency_document_counters', function (Blueprint $table) {
            $table->string('document_type', 20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('agency_document_counters', function (Blueprint $table) {
            $table->string('document_type', 10)->change();
        });
    }
};
