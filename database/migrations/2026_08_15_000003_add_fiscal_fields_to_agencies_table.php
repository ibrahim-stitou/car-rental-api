<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoice/legal identifiers used to belong to a single global
     * "Paramètres > Entreprise" settings row, but agencies are the real
     * invoicing entities (each billing document belongs to one agency) —
     * so this info now lives per-agency instead.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('legal_form')->nullable()->after('email');  // Forme juridique
            $table->string('capital')->nullable()->after('legal_form'); // Capital social
            $table->string('rc')->nullable()->after('capital');         // Registre de commerce
            $table->string('tax_id')->nullable()->after('rc');          // Identifiant Fiscal (IF)
            $table->string('patente')->nullable()->after('tax_id');
            $table->string('ice')->nullable()->after('patente');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['legal_form', 'capital', 'rc', 'tax_id', 'patente', 'ice']);
        });
    }
};
