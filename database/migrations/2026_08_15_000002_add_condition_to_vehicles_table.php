<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Physical condition of the vehicle (distinct from `status`, which is
     * operational availability) — printed on the reservation contract so
     * the client and agency agree on the vehicle's state (e.g. "accidenté")
     * at the time of the rental, independently of any per-pickup/per-return
     * damage notes.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->enum('condition', ['bon_etat', 'leger_dommage', 'accidente', 'hors_service'])
                ->default('bon_etat')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};
