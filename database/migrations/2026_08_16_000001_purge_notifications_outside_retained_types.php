<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Notification scope was trimmed down to exactly three categories:
     * insurance expiry, technical-inspection expiry, and "contrat terminé"
     * (an active reservation whose planned return_date is more than 1 day
     * past, per SendScheduledAlerts::alertReservationsOverdue()) — no more
     * routine reservation create/confirm/activate/cancel/complete noise.
     * This purges historical notification rows that don't match that set.
     *
     * The semantic type lives at data->type (every row's own `type` column
     * is always the notification CLASS name, App\...\AppNotification, since
     * this uses Laravel's database notification channel — not the business
     * type, which toDatabase() nests inside the JSON `data` payload).
     */
    private const KEEP_TYPES = [
        'insurance.expiring_soon',
        'insurance.expired',
        'inspection.expiring_soon',
        'inspection.expired',
        'reservation.overdue',
    ];

    public function up(): void
    {
        DB::table('notifications')
            ->where(function ($query) {
                $query->whereNotIn('data->type', self::KEEP_TYPES)
                    ->orWhereNull('data->type');
            })
            ->delete();
    }

    public function down(): void
    {
        // Deleted rows can't be restored — this is a one-way cleanup.
    }
};
