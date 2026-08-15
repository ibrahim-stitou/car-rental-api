<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('rental_unit', ['day', 'hour'])->default('day')->after('return_date');
            // Snapshotted from the vehicle at creation time, exactly like
            // daily_rate already is — only meaningful when rental_unit = 'hour'.
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('daily_rate');
            $table->integer('total_hours')->nullable()->after('total_days');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['rental_unit', 'hourly_rate', 'total_hours']);
        });
    }
};
