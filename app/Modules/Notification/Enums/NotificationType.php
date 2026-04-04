<?php

namespace App\Modules\Notification\Enums;

enum NotificationType: string
{
    // ─── Reservation ───
    case RESERVATION_CREATED = 'reservation.created';
    case RESERVATION_CONFIRMED = 'reservation.confirmed';
    case RESERVATION_ACTIVATED = 'reservation.activated';
    case RESERVATION_COMPLETED = 'reservation.completed';
    case RESERVATION_CANCELLED = 'reservation.cancelled';
    case RESERVATION_OVERDUE = 'reservation.overdue';
    case RESERVATION_REMINDER_PICKUP = 'reservation.reminder.pickup';
    case RESERVATION_REMINDER_RETURN = 'reservation.reminder.return';

    // ─── Vehicle ───
    case VEHICLE_STATUS_CHANGED = 'vehicle.status_changed';
    case VEHICLE_MILEAGE_ALERT = 'vehicle.mileage_alert';

    // ─── Insurance ───
    case INSURANCE_EXPIRING_SOON = 'insurance.expiring_soon';
    case INSURANCE_EXPIRED = 'insurance.expired';
    case INSURANCE_CREATED = 'insurance.created';

    // ─── Technical Inspection ───
    case INSPECTION_EXPIRING_SOON = 'inspection.expiring_soon';
    case INSPECTION_EXPIRED = 'inspection.expired';
    case INSPECTION_FAILED = 'inspection.failed';

    // ─── Vignette ───
    case VIGNETTE_EXPIRING_SOON = 'vignette.expiring_soon';
    case VIGNETTE_EXPIRED = 'vignette.expired';
    case VIGNETTE_UNPAID = 'vignette.unpaid';

    // ─── Maintenance ───
    case MAINTENANCE_SCHEDULED = 'maintenance.scheduled';
    case MAINTENANCE_OVERDUE = 'maintenance.overdue';
    case MAINTENANCE_COMPLETED = 'maintenance.completed';

    // ─── Billing ───
    case BILLING_CREATED = 'billing.created';
    case BILLING_APPROVED = 'billing.approved';
    case BILLING_PAID = 'billing.paid';
    case BILLING_OVERDUE = 'billing.overdue';

    // ─── Client ───
    case CLIENT_BLACKLISTED = 'client.blacklisted';
    case CLIENT_LICENSE_EXPIRING = 'client.license_expiring';

    // ─── User / System ───
    case USER_CREATED = 'user.created';
    case USER_DEACTIVATED = 'user.deactivated';
    case SYSTEM_ALERT = 'system.alert';

    public function label(): string
    {
        return match ($this) {
            self::RESERVATION_CREATED => 'Nouvelle réservation',
            self::RESERVATION_CONFIRMED => 'Réservation confirmée',
            self::RESERVATION_ACTIVATED => 'Réservation activée (départ)',
            self::RESERVATION_COMPLETED => 'Réservation terminée (retour)',
            self::RESERVATION_CANCELLED => 'Réservation annulée',
            self::RESERVATION_OVERDUE => 'Réservation en retard',
            self::RESERVATION_REMINDER_PICKUP => 'Rappel : départ imminent',
            self::RESERVATION_REMINDER_RETURN => 'Rappel : retour imminent',
            self::VEHICLE_STATUS_CHANGED => 'Statut véhicule modifié',
            self::VEHICLE_MILEAGE_ALERT => 'Alerte kilométrage véhicule',
            self::INSURANCE_EXPIRING_SOON => 'Assurance bientôt expirée',
            self::INSURANCE_EXPIRED => 'Assurance expirée',
            self::INSURANCE_CREATED => 'Nouvelle assurance',
            self::INSPECTION_EXPIRING_SOON => 'Visite technique bientôt expirée',
            self::INSPECTION_EXPIRED => 'Visite technique expirée',
            self::INSPECTION_FAILED => 'Visite technique échouée',
            self::VIGNETTE_EXPIRING_SOON => 'Vignette bientôt expirée',
            self::VIGNETTE_EXPIRED => 'Vignette expirée',
            self::VIGNETTE_UNPAID => 'Vignette impayée',
            self::MAINTENANCE_SCHEDULED => 'Maintenance programmée',
            self::MAINTENANCE_OVERDUE => 'Maintenance en retard',
            self::MAINTENANCE_COMPLETED => 'Maintenance terminée',
            self::BILLING_CREATED => 'Nouveau document de facturation',
            self::BILLING_APPROVED => 'Document approuvé',
            self::BILLING_PAID => 'Document payé',
            self::BILLING_OVERDUE => 'Facture en retard de paiement',
            self::CLIENT_BLACKLISTED => 'Client mis en liste noire',
            self::CLIENT_LICENSE_EXPIRING => 'Permis client bientôt expiré',
            self::USER_CREATED => 'Nouvel utilisateur créé',
            self::USER_DEACTIVATED => 'Utilisateur désactivé',
            self::SYSTEM_ALERT => 'Alerte système',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::RESERVATION_OVERDUE, self::INSURANCE_EXPIRED, self::INSPECTION_EXPIRED,
            self::VIGNETTE_EXPIRED, self::MAINTENANCE_OVERDUE, self::BILLING_OVERDUE,
            self::INSPECTION_FAILED => 'critical',

            self::INSURANCE_EXPIRING_SOON, self::INSPECTION_EXPIRING_SOON,
            self::VIGNETTE_EXPIRING_SOON, self::VIGNETTE_UNPAID,
            self::CLIENT_BLACKLISTED, self::CLIENT_LICENSE_EXPIRING,
            self::USER_DEACTIVATED, self::VEHICLE_MILEAGE_ALERT,
            self::RESERVATION_CANCELLED => 'warning',

            default => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::RESERVATION_CREATED, self::RESERVATION_CONFIRMED, self::RESERVATION_ACTIVATED,
            self::RESERVATION_COMPLETED, self::RESERVATION_CANCELLED, self::RESERVATION_OVERDUE,
            self::RESERVATION_REMINDER_PICKUP, self::RESERVATION_REMINDER_RETURN => 'calendar',

            self::VEHICLE_STATUS_CHANGED, self::VEHICLE_MILEAGE_ALERT => 'car',

            self::INSURANCE_EXPIRING_SOON, self::INSURANCE_EXPIRED, self::INSURANCE_CREATED => 'shield',

            self::INSPECTION_EXPIRING_SOON, self::INSPECTION_EXPIRED, self::INSPECTION_FAILED => 'clipboard-check',

            self::VIGNETTE_EXPIRING_SOON, self::VIGNETTE_EXPIRED, self::VIGNETTE_UNPAID => 'ticket',

            self::MAINTENANCE_SCHEDULED, self::MAINTENANCE_OVERDUE, self::MAINTENANCE_COMPLETED => 'wrench',

            self::BILLING_CREATED, self::BILLING_APPROVED, self::BILLING_PAID, self::BILLING_OVERDUE => 'file-invoice',

            self::CLIENT_BLACKLISTED, self::CLIENT_LICENSE_EXPIRING => 'user-x',

            self::USER_CREATED, self::USER_DEACTIVATED => 'user',

            self::SYSTEM_ALERT => 'bell',
        };
    }
}

