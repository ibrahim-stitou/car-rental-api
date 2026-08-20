<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * document_number was globally unique, which no longer makes sense once
     * numbering is per-agency — two distinct agencies (distinct legal
     * entities) both issuing "FA-000001" is expected and correct, not a
     * collision. Uniqueness now only needs to hold within one agency.
     */
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->dropUnique('billing_documents_document_number_unique');
            $table->unique(['agency_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->dropUnique(['agency_id', 'document_number']);
            $table->unique('document_number');
        });
    }
};
