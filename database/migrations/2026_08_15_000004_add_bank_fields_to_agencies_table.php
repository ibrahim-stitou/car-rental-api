<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bank details for the "account to be credited" (the lessor / agency owner)
     * shown on LLD corporate contracts. Each agency is a distinct legal entity
     * with its own banking, so this lives per-agency like the fiscal fields.
     */
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('ice');      // Nom de la banque
            $table->string('bank_branch')->nullable()->after('bank_name'); // Agence / centre d'affaires
            $table->string('bank_address')->nullable()->after('bank_branch'); // Adresse de la banque
            $table->string('bank_account')->nullable()->after('bank_address'); // Numéro de compte
            $table->string('bank_rib')->nullable()->after('bank_account');     // RIB
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_branch', 'bank_address', 'bank_account', 'bank_rib']);
        });
    }
};