<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Document numbering used to be one global sequence shared by every
     * agency (via the 'counters' Settings group) — wrong once agencies
     * turned out to be distinct legal entities (LIMITLESS RENT CAR, ITIBAN
     * RENT CAR, MIMO CAR), each of which needs its own gapless sequential
     * numbering for tax compliance. One row per (agency, document type).
     */
    public function up(): void
    {
        Schema::create('agency_document_counters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->string('document_type', 10); // fa, av, dv, bc, bl, br, lld
            $table->string('prefix');
            $table->string('separator', 5)->default('-');
            $table->unsignedTinyInteger('digits')->default(6);
            $table->unsignedInteger('current')->default(0);
            $table->timestamps();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('cascade');
            $table->unique(['agency_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_document_counters');
    }
};
