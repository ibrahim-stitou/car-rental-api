<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Options choisies à la création du contrat LLD (locataire personne
     * morale) et reprises dans la section « Loyer » du contrat corporate :
     * assurances, voiture de remplacement, pneumatiques, franchise
     * tous-risques, carburant et kilométrages.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->boolean('insurance_included')->default(false);
            $table->boolean('replacement_vehicle_included')->default(false);
            $table->string('replacement_vehicle')->nullable();
            $table->string('tire_replacement')->nullable();
            $table->decimal('all_risk_franchise_pct', 5, 2)->nullable();
            $table->boolean('fuel_included')->default(false);
            $table->decimal('extra_km_rate', 10, 2)->nullable();
            $table->integer('return_km')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'insurance_included', 'replacement_vehicle_included', 'replacement_vehicle',
                'tire_replacement', 'all_risk_franchise_pct', 'fuel_included',
                'extra_km_rate', 'return_km',
            ]);
        });
    }
};