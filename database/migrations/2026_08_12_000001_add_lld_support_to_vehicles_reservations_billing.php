<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Independent from daily/hourly rate — LLD (location longue durée)
            // pricing is individually negotiated per contract, this is just a
            // convenience default to pre-fill from when one exists.
            $table->decimal('monthly_rate', 10, 2)->nullable()->after('hourly_rate');
        });

        // Enum columns aren't alterable via the Schema Builder without
        // doctrine/dbal — raw MODIFY COLUMN mirrors how 'day'/'hour' were
        // already defined on this column.
        DB::statement("ALTER TABLE reservations MODIFY COLUMN rental_unit ENUM('day','hour','month') NOT NULL DEFAULT 'day'");

        Schema::table('reservations', function (Blueprint $table) {
            // Snapshotted from the vehicle (or entered manually) at creation,
            // exactly like daily_rate/hourly_rate already are.
            $table->decimal('monthly_rate', 10, 2)->nullable()->after('hourly_rate');
            $table->integer('total_months')->nullable()->after('total_hours');
        });

        DB::statement("ALTER TABLE billing_documents MODIFY COLUMN type ENUM('BC','BR','BL','DV','FA','AV','LLD') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE billing_documents MODIFY COLUMN type ENUM('BC','BR','BL','DV','FA','AV') NOT NULL");

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['monthly_rate', 'total_months']);
        });

        DB::statement("ALTER TABLE reservations MODIFY COLUMN rental_unit ENUM('day','hour') NOT NULL DEFAULT 'day'");

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('monthly_rate');
        });
    }
};
