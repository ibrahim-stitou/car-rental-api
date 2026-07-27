<?php

namespace App\Modules\Notification\Commands;

use App\Models\BillingDocument;
use App\Models\Client;
use App\Models\Insurance;
use App\Models\Maintenance;
use App\Models\Reservation;
use App\Models\TechnicalInspection;
use App\Models\Vignette;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Console\Command;

class SendScheduledAlerts extends Command
{
    protected $signature = 'notifications:send-alerts
                            {--type=all : Type d\'alerte (all|reservations|insurances|inspections|vignettes|maintenances|billing|clients)}
                            {--days=30 : Nombre de jours avant expiration pour alerter}';

    protected $description = 'Envoyer les alertes automatiques (expirations, retards, rappels)';

    public function __construct(protected NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = $this->option('type');
        $days = (int) $this->option('days');

        $this->info("🔔 Envoi des alertes automatiques (type: {$type}, jours: {$days})...");

        $totalSent = 0;

        if (in_array($type, ['all', 'reservations'])) {
            $totalSent += $this->alertReservationsOverdue();
            $totalSent += $this->alertReservationReminders();
        }

        if (in_array($type, ['all', 'insurances'])) {
            $totalSent += $this->alertInsurances($days);
        }

        if (in_array($type, ['all', 'inspections'])) {
            $totalSent += $this->alertInspections($days);
        }

        if (in_array($type, ['all', 'vignettes'])) {
            $totalSent += $this->alertVignettes($days);
        }

        if (in_array($type, ['all', 'maintenances'])) {
            $totalSent += $this->alertMaintenances();
        }

        if (in_array($type, ['all', 'billing'])) {
            $totalSent += $this->alertBillingOverdue();
        }

        if (in_array($type, ['all', 'clients'])) {
            $totalSent += $this->alertClientLicenses($days);
        }

        $this->info("✅ Terminé ! {$totalSent} alerte(s) envoyée(s).");
        return self::SUCCESS;
    }

    protected function alertReservationsOverdue(): int
    {
        $reservations = Reservation::overdue()->with(['vehicle', 'client', 'agency'])->get();
        $count = 0;
        foreach ($reservations as $reservation) {
            $this->notificationService->notifyReservationOverdue($reservation);
            $count++;
        }
        $this->line("  📅 Réservations en retard : {$count}");
        return $count;
    }

    protected function alertReservationReminders(): int
    {
        $count = 0;

        // Rappel départ dans 24h
        $pickups = Reservation::where('status', 'confirmed')
            ->whereBetween('pickup_date', [now(), now()->addDay()])
            ->with(['vehicle', 'client', 'agency'])
            ->get();

        foreach ($pickups as $reservation) {
            $this->notificationService->sendToAgencyByPermission(
                $reservation->agency_id,
                \App\Modules\Notification\Enums\NotificationType::RESERVATION_REMINDER_PICKUP,
                [
                    'title'       => "Rappel : départ demain — {$reservation->reservation_number}",
                    'body'        => "{$reservation->client->full_name} doit récupérer {$reservation->vehicle->full_name} demain.",
                    'entity_type' => 'reservation',
                    'entity_id'   => $reservation->id,
                    'action_url'  => "/reservations/{$reservation->id}",
                ],
                ['view-reservation']
            );
            $count++;
        }

        // Rappel retour dans 24h
        $returns = Reservation::where('status', 'active')
            ->whereBetween('return_date', [now(), now()->addDay()])
            ->with(['vehicle', 'client', 'agency'])
            ->get();

        foreach ($returns as $reservation) {
            $this->notificationService->sendToAgencyByPermission(
                $reservation->agency_id,
                \App\Modules\Notification\Enums\NotificationType::RESERVATION_REMINDER_RETURN,
                [
                    'title'       => "Rappel : retour demain — {$reservation->reservation_number}",
                    'body'        => "{$reservation->client->full_name} doit rendre {$reservation->vehicle->full_name} demain.",
                    'entity_type' => 'reservation',
                    'entity_id'   => $reservation->id,
                    'action_url'  => "/reservations/{$reservation->id}",
                ],
                ['view-reservation']
            );
            $count++;
        }

        $this->line("  📅 Rappels réservations : {$count}");
        return $count;
    }

    protected function alertInsurances(int $days): int
    {
        $count = 0;

        // Bientôt expirées
        $expiring = Insurance::expiringSoon($days)->where('is_active', true)->with(['vehicle.agency'])->get();
        foreach ($expiring as $insurance) {
            $this->notificationService->notifyInsuranceExpiringSoon($insurance);
            $count++;
        }

        // Expirées
        $expired = Insurance::expired()->where('is_active', true)->with(['vehicle.agency'])->get();
        foreach ($expired as $insurance) {
            $this->notificationService->notifyInsuranceExpired($insurance);
            $count++;
        }

        $this->line("  🛡️  Alertes assurances : {$count}");
        return $count;
    }

    protected function alertInspections(int $days): int
    {
        $count = 0;

        $expiring = TechnicalInspection::expiringSoon($days)->with(['vehicle.agency'])->get();
        foreach ($expiring as $inspection) {
            $this->notificationService->notifyInspectionExpiringSoon($inspection);
            $count++;
        }

        $expired = TechnicalInspection::expired()->with(['vehicle.agency'])->get();
        foreach ($expired as $inspection) {
            $this->notificationService->notifyInspectionExpired($inspection);
            $count++;
        }

        $this->line("  🔧 Alertes visites techniques : {$count}");
        return $count;
    }

    protected function alertVignettes(int $days): int
    {
        $count = 0;

        $expiring = Vignette::where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->with(['vehicle.agency'])->get();
        foreach ($expiring as $vignette) {
            $this->notificationService->notifyVignetteExpiringSoon($vignette);
            $count++;
        }

        $expired = Vignette::expired()->with(['vehicle.agency'])->get();
        foreach ($expired as $vignette) {
            $this->notificationService->notifyVignetteExpired($vignette);
            $count++;
        }

        $this->line("  🎫 Alertes vignettes : {$count}");
        return $count;
    }

    protected function alertMaintenances(): int
    {
        $count = 0;
        $overdue = Maintenance::overdue()->with(['vehicle.agency'])->get();
        foreach ($overdue as $maintenance) {
            $this->notificationService->notifyMaintenanceOverdue($maintenance);
            $count++;
        }
        $this->line("  🔩 Maintenances en retard : {$count}");
        return $count;
    }

    protected function alertBillingOverdue(): int
    {
        $count = 0;
        $overdue = BillingDocument::where('type', 'FA')
            ->whereIn('status', ['approved', 'pending'])
            ->where('due_date', '<', now())
            ->where('balance', '>', 0)
            ->with(['agency'])
            ->get();

        foreach ($overdue as $document) {
            $this->notificationService->notifyBillingOverdue($document);
            $count++;
        }
        $this->line("  💰 Factures en retard : {$count}");
        return $count;
    }

    protected function alertClientLicenses(int $days): int
    {
        $count = 0;
        $clients = Client::where('is_blacklisted', false)
            ->whereNotNull('driving_license_expiry')
            ->where('driving_license_expiry', '>=', now())
            ->where('driving_license_expiry', '<=', now()->addDays($days))
            ->with(['agencies'])
            ->get();

        foreach ($clients as $client) {
            $this->notificationService->notifyClientLicenseExpiring($client);
            $count++;
        }
        $this->line("  🪪 Permis clients bientôt expirés : {$count}");
        return $count;
    }
}

