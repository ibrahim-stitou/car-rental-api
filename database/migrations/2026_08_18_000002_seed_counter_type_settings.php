<?php

use App\Models\Reservation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "reservation" starts shared=true (one running sequence for every
     * agency, per user decision — the reservation number generator is
     * rewritten in the same change to actually use this counter, replacing
     * the old year+sequence "26-0001" format; existing reservation numbers
     * are left untouched, only new ones use the new scheme). `current`
     * starts at the count of real (non-legacy) reservations already in the
     * system — not 0 — so the new numbering continues where production
     * already is instead of visibly restarting from scratch. Legacy/
     * migrated reservations (legacy_id set, 'ARCHIVE-<id>' numbers) never
     * fed a counter and are excluded; withTrashed() so a since-deleted
     * reservation still counts, exactly like the old generator did.
     *
     * The 7 billing types start shared=false (each agency keeps the
     * per-agency counter already seeded in agency_document_counters) — their
     * prefix/separator/digits here just carry over the old global values
     * for consistency if the user ever flips one to shared, seeded with the
     * highest `current` already reached by any agency so switching to
     * shared can never go backwards past an already-issued number.
     */
    public function up(): void
    {
        $reservationCount = Reservation::withTrashed()->whereNull('legacy_id')->count();

        DB::table('counter_type_settings')->insert([
            'document_type' => 'reservation', 'shared' => true,
            'prefix' => 'RES', 'separator' => '-', 'digits' => 6, 'current' => $reservationCount,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $types = ['fa', 'av', 'dv', 'bc', 'bl', 'br', 'lld'];
        foreach ($types as $type) {
            $sample = DB::table('agency_document_counters')->where('document_type', $type)->first();
            $maxCurrent = (int) DB::table('agency_document_counters')->where('document_type', $type)->max('current');

            DB::table('counter_type_settings')->insert([
                'document_type' => $type,
                'shared'        => false,
                'prefix'        => $sample->prefix ?? strtoupper($type),
                'separator'     => $sample->separator ?? '-',
                'digits'        => $sample->digits ?? 6,
                'current'       => $maxCurrent,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('counter_type_settings')->truncate();
    }
};
