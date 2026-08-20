<?php

use App\Models\Reservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The shared "reservation" counter was seeded starting at 0 — wrong on
     * an environment that already has real reservations in production.
     * Legacy/migrated reservations (legacy_id set, 'ARCHIVE-<id>' numbers)
     * never fed any counter and are excluded; only reservations created
     * through the live system count. withTrashed() so a since-deleted
     * reservation still counts (mirrors the old generateReservationNumber()
     * logic), so the next number can never repeat one already issued.
     */
    public function up(): void
    {
        $count = Reservation::withTrashed()->whereNull('legacy_id')->count();

        DB::table('counter_type_settings')
            ->where('document_type', 'reservation')
            ->update(['current' => $count, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('counter_type_settings')
            ->where('document_type', 'reservation')
            ->update(['current' => 0, 'updated_at' => now()]);
    }
};
