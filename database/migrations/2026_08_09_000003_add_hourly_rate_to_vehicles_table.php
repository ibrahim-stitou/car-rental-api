<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Independent from daily_rate — a rental business typically prices
            // a short hourly booking higher than daily_rate/24 to discourage
            // very short rentals, so it's not derived. Nullable: a vehicle
            // without an hourly rate simply isn't offered for hourly booking.
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('daily_rate');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
    }
};
