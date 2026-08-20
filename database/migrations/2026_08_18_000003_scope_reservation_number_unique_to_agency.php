<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "reservation" numbering defaults to shared (one global sequence), but
     * the user can flip it to per-agency isolated at any time — at which
     * point two different agencies legitimately producing the same
     * "RES-000001" must not collide, exactly like billing_documents.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique('reservations_reservation_number_unique');
            $table->unique(['agency_id', 'reservation_number']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique(['agency_id', 'reservation_number']);
            $table->unique('reservation_number');
        });
    }
};
