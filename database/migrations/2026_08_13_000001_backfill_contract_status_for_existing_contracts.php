<?php

use App\Models\Reservation;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * The 2026_08_09_000001 migration added `contract_status` defaulting to
     * 'not_generated' — fine for new rows, but any reservation that already
     * had a contract generated *before* that column existed was left
     * stranded at the default despite genuinely having a locked PDF (its
     * `contract_generated_at` was already set). Symptom: the detail page
     * shows "Statut du contrat : Non généré" and hides the
     * Dévalider/Régénérer buttons even though a version history exists.
     * Backfill those rows to 'valid' and log a 'generated' event so the
     * history timeline isn't empty going forward.
     */
    public function up(): void
    {
        Reservation::whereNotNull('contract_generated_at')
            ->where('contract_status', 'not_generated')
            ->each(function (Reservation $reservation) {
                $reservation->update(['contract_status' => 'valid']);
                $reservation->contractEvents()->create([
                    'actor_id'   => null,
                    'event_type' => 'generated',
                    'reason'     => 'Statut rétabli automatiquement (contrat déjà généré avant l\'ajout du suivi de statut)',
                    'snapshot'   => [
                        'pickup_date'         => optional($reservation->pickup_date)->toIso8601String(),
                        'return_date'         => optional($reservation->return_date)->toIso8601String(),
                        'pickup_location'     => $reservation->pickup_location,
                        'return_location'     => $reservation->return_location,
                        'daily_rate'          => $reservation->daily_rate,
                        'discount_percentage' => $reservation->discount_percentage,
                        'additional_fees'     => $reservation->additional_fees,
                        'total_amount'        => $reservation->total_amount,
                    ],
                ]);
            });
    }

    public function down(): void
    {
        // Not reversible in a meaningful way — we can't distinguish rows this
        // backfilled from ones later validated the normal way. No-op.
    }
};
