<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La colonne technique legacy_id est ajoutée en production via
     * database/migration-scripts/migrate_legacy_data.sql mais absente des
     * migrations : toute requête la référencant (ReservationService,
     * BaseRepository, migrations de compteurs 2026_08_18_000002 /
     * 2026_08_19_000001, filtres AGDA/DASHBOARD...) échoue sur une
     * installation fraîche ou en environnement de test (RefreshDatabase).
     *
     * Le timestamp est volontairement positionné AVANT ces migrations de
     * compteurs pour garantir que la colonne existe au moment où elles
     * l'interrogent. Cette migration est idempotente (guards hasColumn)
     * pour être compatible avec les bases déjà migrées par le script SQL.
     */
    public function up(): void
    {
        foreach ($this->tables() as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'legacy_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->integer('legacy_id')->nullable();
                    $blueprint->index('legacy_id', 'idx_legacy_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'legacy_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropIndex('idx_legacy_id');
                    $blueprint->dropColumn('legacy_id');
                });
            }
        }
    }

    /** @return string[] */
    private function tables(): array
    {
        return ['agencies', 'users', 'clients', 'vehicles', 'reservations', 'billing_documents', 'insurances', 'vignettes', 'technical_inspections'];
    }
};