<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Maintenances - description libre agent
        if (!Schema::hasColumn('maintenances', 'agent_notes')) {
            Schema::table('maintenances', function (Blueprint $table) {
                $table->text('agent_notes')->nullable()->after('description');
            });
        }

        // Reservations - description libre
        if (!Schema::hasColumn('reservations', 'agent_notes')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->text('agent_notes')->nullable()->after('notes');
            });
        }

        // Insurances
        if (!Schema::hasColumn('insurances', 'agent_notes')) {
            Schema::table('insurances', function (Blueprint $table) {
                $table->text('agent_notes')->nullable();
            });
        }

        // Technical Inspections
        if (!Schema::hasColumn('technical_inspections', 'agent_notes')) {
            Schema::table('technical_inspections', function (Blueprint $table) {
                $table->text('agent_notes')->nullable();
            });
        }

        // Vignettes
        if (!Schema::hasColumn('vignettes', 'agent_notes')) {
            Schema::table('vignettes', function (Blueprint $table) {
                $table->text('agent_notes')->nullable();
            });
        }

        // Expenses
        if (!Schema::hasColumn('expenses', 'agent_notes')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->text('agent_notes')->nullable()->after('notes');
            });
        }

        // Clients
        if (!Schema::hasColumn('clients', 'agent_notes')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->text('agent_notes')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['maintenances', 'reservations', 'insurances', 'technical_inspections', 'vignettes', 'expenses', 'clients'] as $table) {
            if (Schema::hasColumn($table, 'agent_notes')) {
                Schema::table($table, fn(Blueprint $t) => $t->dropColumn('agent_notes'));
            }
        }
    }
};
