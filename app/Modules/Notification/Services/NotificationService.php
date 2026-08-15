<?php

namespace App\Modules\Notification\Services;

use App\Models\User;
use App\Modules\Notification\Enums\NotificationType;
use App\Modules\Notification\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Collection;

class NotificationService
{
    // ══════════════════════════════════════════
    //  CORE — Envoyer des notifications
    // ══════════════════════════════════════════

    /**
     * Envoyer une notification à un utilisateur spécifique.
     */
    public function sendToUser(User $user, NotificationType $type, array $payload = [], array $channels = ['database', 'mail']): void
    {
        $user->notify(new AppNotification($type, $payload, $channels));
    }

    /**
     * Envoyer une notification à plusieurs utilisateurs.
     */
    public function sendToUsers(Collection $users, NotificationType $type, array $payload = [], array $channels = ['database', 'mail']): void
    {
        Notification::send($users, new AppNotification($type, $payload, $channels));
    }

    /**
     * Envoyer à tous les utilisateurs d'une agence ayant l'un des rôles donnés.
     * Rôles fournis dynamiquement (ex: choix manuel d'un admin) — un nom qui
     * n'existe plus en base ne doit jamais faire planter l'envoi.
     */
    public function sendToAgency(string $agencyId, NotificationType $type, array $payload = [], array $roles = []): void
    {
        $query = User::whereHas('agencies', fn ($a) => $a->where('agencies.id', $agencyId))->where('is_active', true);
        if (!empty($roles)) {
            $query->whereHas('roles', fn ($r) => $r->where('guard_name', 'api')->whereIn('name', $roles));
        }
        $users = $query->get();
        $this->sendToUsers($users, $type, $payload);
    }

    /**
     * Envoyer à tous les utilisateurs d'une agence disposant d'au moins une
     * des permissions données (via rôle ou permission directe). Les permissions
     * métier (ex: view-reservation) sont le seul identifiant stable pour cibler
     * "qui doit être notifié" — contrairement aux noms de rôles, qui sont
     * entièrement dynamiques et peuvent être renommés/supprimés à tout moment.
     */
    public function sendToAgencyByPermission(string $agencyId, NotificationType $type, array $payload = [], array $permissions = []): void
    {
        $query = User::whereHas('agencies', fn ($a) => $a->where('agencies.id', $agencyId))->where('is_active', true);
        $this->scopeAnyPermission($query, $permissions);
        $users = $query->get();
        $this->sendToUsers($users, $type, $payload);
    }

    /**
     * Envoyer aux administrateurs : le rôle "super-admin" est le seul dont
     * l'existence est garantie, complété dynamiquement par tout utilisateur
     * disposant de la permission "manage-settings" (via un rôle quelconque).
     */
    public function sendToAdmins(NotificationType $type, array $payload = []): void
    {
        $users = $this->adminUsersQuery()->get();
        $this->sendToUsers($users, $type, $payload);
    }

    /**
     * Requête des administrateurs : rôle "super-admin" (seul rôle garanti)
     * complété dynamiquement par tout utilisateur disposant de la permission
     * "manage-settings" via un rôle quelconque.
     */
    public function adminUsersQuery()
    {
        return User::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->where('guard_name', 'api')->where('name', 'super-admin'));
                $this->scopeAnyPermission($q, ['manage-settings'], 'or');
            });
    }

    /**
     * Restreint une requête aux utilisateurs ayant au moins une des permissions
     * données (permission directe ou via un rôle), sans jamais lever d'exception
     * si le nom de permission n'existe plus en base.
     */
    private function scopeAnyPermission($query, array $permissions, string $boolean = 'and'): void
    {
        if (empty($permissions)) {
            return;
        }
        $query->where(function ($q) use ($permissions) {
            $q->whereHas('permissions', fn ($p) => $p->where('guard_name', 'api')->whereIn('name', $permissions))
              ->orWhereHas('roles.permissions', fn ($p) => $p->where('guard_name', 'api')->whereIn('name', $permissions));
        }, boolean: $boolean === 'or' ? 'or' : 'and');
    }

    /**
     * Notification database uniquement (pas de mail).
     */
    public function notifyDatabaseOnly(User $user, NotificationType $type, array $payload = []): void
    {
        $user->notify(new AppNotification($type, $payload, ['database']));
    }

    // ══════════════════════════════════════════
    //  LECTURE — Récupérer les notifications
    // ══════════════════════════════════════════

    public function getUserNotifications(User $user, int $perPage = 20, ?string $filterType = null, ?string $filterSeverity = null)
    {
        $query = $user->notifications();

        if ($filterType) {
            $query->where('data->type', $filterType);
        }
        if ($filterSeverity) {
            $query->where('data->severity', $filterSeverity);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getUnreadNotifications(User $user, int $limit = 50)
    {
        return $user->unreadNotifications()->orderBy('created_at', 'desc')->take($limit)->get();
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        return false;
    }

    public function markAllAsRead(User $user): int
    {
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications->markAsRead();
        return $count;
    }

    public function deleteNotification(User $user, string $notificationId): bool
    {
        return (bool) $user->notifications()->where('id', $notificationId)->delete();
    }

    public function deleteAllRead(User $user): int
    {
        return $user->notifications()->whereNotNull('read_at')->delete();
    }

    public function getSummary(User $user): array
    {
        $unread = $user->unreadNotifications;
        return [
            'total'        => $user->notifications()->count(),
            'unread'       => $unread->count(),
            'by_severity'  => [
                'critical' => $unread->filter(fn($n) => ($n->data['severity'] ?? '') === 'critical')->count(),
                'warning'  => $unread->filter(fn($n) => ($n->data['severity'] ?? '') === 'warning')->count(),
                'info'     => $unread->filter(fn($n) => ($n->data['severity'] ?? '') === 'info')->count(),
            ],
            'latest'       => $unread->take(5)->map(fn($n) => [
                'id'         => $n->id,
                'title'      => $n->data['title'] ?? '',
                'severity'   => $n->data['severity'] ?? 'info',
                'icon'       => $n->data['icon'] ?? 'bell',
                'created_at' => $n->created_at->toISOString(),
            ])->values(),
        ];
    }

    // ══════════════════════════════════════════
    //  RESERVATION — Notifications métier
    // ══════════════════════════════════════════

    public function notifyReservationConfirmed(\App\Models\Reservation $reservation): void
    {
        $reservation->loadMissing(['vehicle', 'client']);
        $payload = [
            'title'       => "Réservation {$reservation->reservation_number} confirmée",
            'body'        => "La réservation de {$reservation->client->full_name} est confirmée.",
            'entity_type' => 'reservation',
            'entity_id'   => $reservation->id,
            'action_url'  => "/reservations/{$reservation->id}",
        ];
        if ($reservation->created_by) {
            $creator = User::find($reservation->created_by);
            if ($creator) $this->sendToUser($creator, NotificationType::RESERVATION_CONFIRMED, $payload);
        }
    }

    public function notifyReservationActivated(\App\Models\Reservation $reservation): void
    {
        $reservation->loadMissing(['vehicle', 'client']);
        $payload = [
            'title'       => "Départ : réservation {$reservation->reservation_number}",
            'body'        => "{$reservation->client->full_name} a pris le véhicule {$reservation->vehicle->full_name}. Retour prévu le {$reservation->return_date->format('d/m/Y')}.",
            'entity_type' => 'reservation',
            'entity_id'   => $reservation->id,
            'action_url'  => "/reservations/{$reservation->id}",
        ];
        $this->sendToAgencyByPermission($reservation->agency_id, NotificationType::RESERVATION_ACTIVATED, $payload, ['view-reservation']);
    }

    public function notifyReservationCancelled(\App\Models\Reservation $reservation): void
    {
        $reservation->loadMissing(['vehicle', 'client']);
        $payload = [
            'title'       => "Réservation {$reservation->reservation_number} annulée",
            'body'        => "Motif : " . ($reservation->cancellation_reason ?? 'Non spécifié'),
            'entity_type' => 'reservation',
            'entity_id'   => $reservation->id,
            'action_url'  => "/reservations/{$reservation->id}",
        ];
        $this->sendToAgencyByPermission($reservation->agency_id, NotificationType::RESERVATION_CANCELLED, $payload, ['view-reservation']);
    }

    public function notifyReservationOverdue(\App\Models\Reservation $reservation): void
    {
        $reservation->loadMissing(['vehicle', 'client']);
        $daysLate = (int) $reservation->return_date->diffInDays(now());
        $payload = [
            'title'        => "⚠️ RETARD : réservation {$reservation->reservation_number}",
            'body'         => "{$reservation->client->full_name} est en retard de {$daysLate} jour(s) avec {$reservation->vehicle->full_name}.",
            'entity_type'  => 'reservation',
            'entity_id'    => $reservation->id,
            'action_url'   => "/reservations/{$reservation->id}",
            'force_mail'   => true,
            'meta'         => ['days_late' => $daysLate],
        ];
        $this->sendToAgencyByPermission($reservation->agency_id, NotificationType::RESERVATION_OVERDUE, $payload, ['view-reservation']);
    }

    // ══════════════════════════════════════════
    //  INSURANCE — Alertes expiration
    // ══════════════════════════════════════════

    public function notifyInsuranceExpiringSoon(\App\Models\Insurance $insurance): void
    {
        $insurance->loadMissing(['vehicle.agency']);
        $daysLeft = (int) now()->diffInDays($insurance->end_date, false);
        $payload = [
            'title'       => "Assurance {$insurance->policy_number} expire dans {$daysLeft} jours",
            'body'        => "Véhicule : {$insurance->vehicle->full_name} ({$insurance->vehicle->registration_number}). Compagnie : {$insurance->insurance_company}.",
            'entity_type' => 'insurance',
            'entity_id'   => $insurance->id,
            'action_url'  => "/insurances/{$insurance->id}",
            'force_mail'  => $daysLeft <= 7,
            'details'     => ['Véhicule' => $insurance->vehicle->full_name, 'Expiration' => $insurance->end_date->format('d/m/Y'), 'Jours restants' => $daysLeft],
        ];
        if ($insurance->vehicle->agency_id) {
            $this->sendToAgencyByPermission($insurance->vehicle->agency_id, NotificationType::INSURANCE_EXPIRING_SOON, $payload, ['view-insurance']);
        }
    }

    public function notifyInsuranceExpired(\App\Models\Insurance $insurance): void
    {
        $insurance->loadMissing(['vehicle.agency']);
        $payload = [
            'title'       => "🔴 Assurance EXPIRÉE : {$insurance->policy_number}",
            'body'        => "L'assurance du véhicule {$insurance->vehicle->full_name} ({$insurance->vehicle->registration_number}) est expirée depuis le {$insurance->end_date->format('d/m/Y')}.",
            'entity_type' => 'insurance',
            'entity_id'   => $insurance->id,
            'action_url'  => "/insurances/{$insurance->id}",
            'force_mail'  => true,
        ];
        if ($insurance->vehicle->agency_id) {
            $this->sendToAgencyByPermission($insurance->vehicle->agency_id, NotificationType::INSURANCE_EXPIRED, $payload, ['view-insurance']);
        }
        $this->sendToAdmins(NotificationType::INSURANCE_EXPIRED, $payload);
    }

    // ══════════════════════════════════════════
    //  TECHNICAL INSPECTION — Alertes expiration
    // ══════════════════════════════════════════

    public function notifyInspectionExpiringSoon(\App\Models\TechnicalInspection $inspection): void
    {
        $inspection->loadMissing(['vehicle.agency']);
        $daysLeft = (int) now()->diffInDays($inspection->expiry_date, false);
        $payload = [
            'title'       => "Visite technique expire dans {$daysLeft} jours",
            'body'        => "Véhicule : {$inspection->vehicle->full_name} ({$inspection->vehicle->registration_number}).",
            'entity_type' => 'technical_inspection',
            'entity_id'   => $inspection->id,
            'action_url'  => "/technical-inspections/{$inspection->id}",
            'force_mail'  => $daysLeft <= 7,
            'details'     => ['Véhicule' => $inspection->vehicle->full_name, 'Expiration' => $inspection->expiry_date->format('d/m/Y')],
        ];
        if ($inspection->vehicle->agency_id) {
            $this->sendToAgencyByPermission($inspection->vehicle->agency_id, NotificationType::INSPECTION_EXPIRING_SOON, $payload, ['view-technical-inspection']);
        }
    }

    public function notifyInspectionExpired(\App\Models\TechnicalInspection $inspection): void
    {
        $inspection->loadMissing(['vehicle.agency']);
        $payload = [
            'title'       => "🔴 Visite technique EXPIRÉE",
            'body'        => "Véhicule {$inspection->vehicle->full_name} ({$inspection->vehicle->registration_number}) — expirée depuis le {$inspection->expiry_date->format('d/m/Y')}.",
            'entity_type' => 'technical_inspection',
            'entity_id'   => $inspection->id,
            'action_url'  => "/technical-inspections/{$inspection->id}",
            'force_mail'  => true,
        ];
        if ($inspection->vehicle->agency_id) {
            $this->sendToAgencyByPermission($inspection->vehicle->agency_id, NotificationType::INSPECTION_EXPIRED, $payload, ['view-technical-inspection']);
        }
    }

    // ══════════════════════════════════════════
    //  VIGNETTE — Alertes expiration / impayé
    // ══════════════════════════════════════════

    public function notifyVignetteExpiringSoon(\App\Models\Vignette $vignette): void
    {
        $vignette->loadMissing(['vehicle.agency']);
        $daysLeft = (int) now()->diffInDays($vignette->expiry_date, false);
        $payload = [
            'title'       => "Vignette {$vignette->year} expire dans {$daysLeft} jours",
            'body'        => "Véhicule : {$vignette->vehicle->full_name} ({$vignette->vehicle->registration_number}).",
            'entity_type' => 'vignette',
            'entity_id'   => $vignette->id,
            'action_url'  => "/vignettes/{$vignette->id}",
            'force_mail'  => $daysLeft <= 7,
        ];
        if ($vignette->vehicle->agency_id) {
            $this->sendToAgencyByPermission($vignette->vehicle->agency_id, NotificationType::VIGNETTE_EXPIRING_SOON, $payload, ['view-vignette']);
        }
    }

    public function notifyVignetteExpired(\App\Models\Vignette $vignette): void
    {
        $vignette->loadMissing(['vehicle.agency']);
        $payload = [
            'title'       => "🔴 Vignette EXPIRÉE — {$vignette->vehicle->registration_number}",
            'body'        => "La vignette {$vignette->year} du véhicule {$vignette->vehicle->full_name} est expirée.",
            'entity_type' => 'vignette',
            'entity_id'   => $vignette->id,
            'action_url'  => "/vignettes/{$vignette->id}",
            'force_mail'  => true,
        ];
        if ($vignette->vehicle->agency_id) {
            $this->sendToAgencyByPermission($vignette->vehicle->agency_id, NotificationType::VIGNETTE_EXPIRED, $payload, ['view-vignette']);
        }
    }

    // ══════════════════════════════════════════
    //  MAINTENANCE — Alertes
    // ══════════════════════════════════════════

    public function notifyMaintenanceScheduled(\App\Models\Maintenance $maintenance): void
    {
        $maintenance->loadMissing(['vehicle.agency']);
        $payload = [
            'title'       => "Maintenance programmée — {$maintenance->vehicle->full_name}",
            'body'        => "Type : {$maintenance->type}. Date : {$maintenance->maintenance_date->format('d/m/Y')}. Priorité : {$maintenance->priority}.",
            'entity_type' => 'maintenance',
            'entity_id'   => $maintenance->id,
            'action_url'  => "/maintenances/{$maintenance->id}",
        ];
        if ($maintenance->vehicle->agency_id) {
            $this->sendToAgencyByPermission($maintenance->vehicle->agency_id, NotificationType::MAINTENANCE_SCHEDULED, $payload, ['view-maintenance']);
        }
    }

    public function notifyMaintenanceOverdue(\App\Models\Maintenance $maintenance): void
    {
        $maintenance->loadMissing(['vehicle.agency']);
        $payload = [
            'title'       => "⚠️ Maintenance en retard — {$maintenance->vehicle->full_name}",
            'body'        => "La maintenance du {$maintenance->maintenance_date->format('d/m/Y')} n'a pas été effectuée.",
            'entity_type' => 'maintenance',
            'entity_id'   => $maintenance->id,
            'action_url'  => "/maintenances/{$maintenance->id}",
            'force_mail'  => true,
        ];
        if ($maintenance->vehicle->agency_id) {
            $this->sendToAgencyByPermission($maintenance->vehicle->agency_id, NotificationType::MAINTENANCE_OVERDUE, $payload, ['view-maintenance']);
        }
    }

    public function notifyMaintenanceCompleted(\App\Models\Maintenance $maintenance): void
    {
        $maintenance->loadMissing(['vehicle.agency']);
        $payload = [
            'title'       => "Maintenance terminée — {$maintenance->vehicle->full_name}",
            'body'        => "Type : {$maintenance->type}. Coût : {$maintenance->cost} DH.",
            'entity_type' => 'maintenance',
            'entity_id'   => $maintenance->id,
            'action_url'  => "/maintenances/{$maintenance->id}",
        ];
        if ($maintenance->vehicle->agency_id) {
            $this->sendToAgencyByPermission($maintenance->vehicle->agency_id, NotificationType::MAINTENANCE_COMPLETED, $payload, ['view-maintenance']);
        }
    }

    // ══════════════════════════════════════════
    //  BILLING — Alertes facturation
    // ══════════════════════════════════════════

    public function notifyBillingCreated(\App\Models\BillingDocument $document): void
    {
        $document->loadMissing(['agency']);
        $payload = [
            'title'       => "Nouveau {$document->type_name} : {$document->document_number}",
            'body'        => "Client : {$document->client_name}. Montant : {$document->total_amount} DH.",
            'entity_type' => 'billing_document',
            'entity_id'   => $document->id,
            'action_url'  => "/billing/{$document->id}",
        ];
        $this->sendToAgencyByPermission($document->agency_id, NotificationType::BILLING_CREATED, $payload, ['view-billing']);
    }

    public function notifyBillingApproved(\App\Models\BillingDocument $document): void
    {
        $payload = [
            'title'       => "{$document->type_name} approuvé : {$document->document_number}",
            'body'        => "Montant : {$document->total_amount} DH.",
            'entity_type' => 'billing_document',
            'entity_id'   => $document->id,
            'action_url'  => "/billing/{$document->id}",
        ];
        if ($document->created_by) {
            $creator = User::find($document->created_by);
            if ($creator) $this->sendToUser($creator, NotificationType::BILLING_APPROVED, $payload);
        }
    }

    public function notifyBillingPaid(\App\Models\BillingDocument $document): void
    {
        $document->loadMissing(['agency']);
        $payload = [
            'title'       => "{$document->type_name} payé : {$document->document_number}",
            'body'        => "Montant payé : {$document->paid_amount} DH. Méthode : {$document->payment_method}.",
            'entity_type' => 'billing_document',
            'entity_id'   => $document->id,
            'action_url'  => "/billing/{$document->id}",
        ];
        $this->sendToAgencyByPermission($document->agency_id, NotificationType::BILLING_PAID, $payload, ['view-billing']);
    }

    public function notifyBillingOverdue(\App\Models\BillingDocument $document): void
    {
        $document->loadMissing(['agency']);
        $daysLate = $document->due_date ? (int) $document->due_date->diffInDays(now()) : 0;
        $payload = [
            'title'       => "⚠️ Facture impayée : {$document->document_number}",
            'body'        => "Client : {$document->client_name}. Solde : {$document->balance} DH. Retard : {$daysLate} jour(s).",
            'entity_type' => 'billing_document',
            'entity_id'   => $document->id,
            'action_url'  => "/billing/{$document->id}",
            'force_mail'  => true,
        ];
        $this->sendToAgencyByPermission($document->agency_id, NotificationType::BILLING_OVERDUE, $payload, ['view-billing']);
    }

    // ══════════════════════════════════════════
    //  CLIENT — Alertes
    // ══════════════════════════════════════════

    public function notifyClientBlacklisted(\App\Models\Client $client): void
    {
        $client->loadMissing(['agencies']);
        $payload = [
            'title'       => "Client mis en liste noire : {$client->full_name}",
            'body'        => "Motif : {$client->blacklist_reason}",
            'entity_type' => 'client',
            'entity_id'   => $client->id,
            'action_url'  => "/clients/{$client->id}",
        ];
        foreach ($client->agencies as $agency) {
            $this->sendToAgencyByPermission($agency->id, NotificationType::CLIENT_BLACKLISTED, $payload, ['view-client']);
        }
    }

    public function notifyClientLicenseExpiring(\App\Models\Client $client): void
    {
        $client->loadMissing(['agencies']);
        $daysLeft = (int) now()->diffInDays($client->driving_license_expiry, false);
        $payload = [
            'title'       => "Permis de {$client->full_name} expire dans {$daysLeft} jours",
            'body'        => "Permis N° {$client->driving_license_number} — Expiration : {$client->driving_license_expiry->format('d/m/Y')}.",
            'entity_type' => 'client',
            'entity_id'   => $client->id,
            'action_url'  => "/clients/{$client->id}",
        ];
        foreach ($client->agencies as $agency) {
            $this->sendToAgencyByPermission($agency->id, NotificationType::CLIENT_LICENSE_EXPIRING, $payload, ['view-client']);
        }
    }

    // ══════════════════════════════════════════
    //  USER — Alertes
    // ══════════════════════════════════════════

    public function notifyUserCreated(User $newUser): void
    {
        $payload = [
            'title'       => "Nouvel utilisateur : {$newUser->full_name}",
            'body'        => "Email : {$newUser->email}. Rôle(s) : " . $newUser->getRoleNames()->join(', '),
            'entity_type' => 'user',
            'entity_id'   => $newUser->id,
            'action_url'  => "/users/{$newUser->id}",
        ];
        $this->sendToAdmins(NotificationType::USER_CREATED, $payload);
    }

    public function notifyUserDeactivated(User $user): void
    {
        $payload = [
            'title'       => "Utilisateur désactivé : {$user->full_name}",
            'body'        => "Le compte de {$user->email} a été désactivé.",
            'entity_type' => 'user',
            'entity_id'   => $user->id,
            'action_url'  => "/users/{$user->id}",
        ];
        $this->sendToAdmins(NotificationType::USER_DEACTIVATED, $payload);
    }

    // ══════════════════════════════════════════
    //  SYSTEM — Alertes génériques
    // ══════════════════════════════════════════

    public function sendSystemAlert(string $title, string $body, ?string $actionUrl = null): void
    {
        $payload = [
            'title'      => $title,
            'body'       => $body,
            'action_url' => $actionUrl,
            'force_mail' => true,
        ];
        $this->sendToAdmins(NotificationType::SYSTEM_ALERT, $payload);
    }
}

