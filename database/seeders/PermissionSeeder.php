<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalog = [
            'Agences' => [
                'view-agency'   => 'Voir les agences',
                'create-agency' => 'Créer une agence',
                'edit-agency'   => 'Modifier une agence',
                'delete-agency' => 'Supprimer une agence',
            ],
            'Véhicules' => [
                'view-vehicle'             => 'Voir les véhicules',
                'create-vehicle'           => 'Créer un véhicule',
                'edit-vehicle'             => 'Modifier un véhicule',
                'delete-vehicle'           => 'Supprimer un véhicule',
                'manage-vehicle-documents' => 'Gérer les documents véhicule',
            ],
            'Visites techniques' => [
                'view-technical-inspection'   => 'Voir les visites techniques',
                'create-technical-inspection' => 'Créer une visite technique',
                'edit-technical-inspection'   => 'Modifier une visite technique',
                'delete-technical-inspection' => 'Supprimer une visite technique',
            ],
            'Vignettes' => [
                'view-vignette'   => 'Voir les vignettes',
                'create-vignette' => 'Créer une vignette',
                'edit-vignette'   => 'Modifier une vignette',
                'delete-vignette' => 'Supprimer une vignette',
            ],
            'Assurances' => [
                'view-insurance'   => 'Voir les assurances',
                'create-insurance' => 'Créer une assurance',
                'edit-insurance'   => 'Modifier une assurance',
                'delete-insurance' => 'Supprimer une assurance',
            ],
            'Clients' => [
                'view-client'      => 'Voir les clients',
                'create-client'    => 'Créer un client',
                'edit-client'      => 'Modifier un client',
                'delete-client'    => 'Supprimer un client',
                'blacklist-client' => 'Liste noire client',
            ],
            'Réservations' => [
                'view-reservation'     => 'Voir les réservations',
                'create-reservation'   => 'Créer une réservation',
                'edit-reservation'     => 'Modifier une réservation',
                'delete-reservation'   => 'Supprimer une réservation',
                'confirm-reservation'  => 'Confirmer une réservation',
                'activate-reservation' => 'Activer une réservation',
                'complete-reservation' => 'Terminer une réservation',
                'cancel-reservation'   => 'Annuler une réservation',
            ],
            'Maintenance' => [
                'view-maintenance'   => 'Voir les maintenances',
                'create-maintenance' => 'Créer une maintenance',
                'edit-maintenance'   => 'Modifier une maintenance',
                'delete-maintenance' => 'Supprimer une maintenance',
            ],
            'Sinistres' => [
                'view-claim'             => 'Voir les sinistres',
                'create-claim'           => 'Déclarer un sinistre',
                'edit-claim'             => 'Modifier un sinistre',
                'delete-claim'           => 'Supprimer un sinistre',
                'manage-claim-documents' => 'Gérer les documents sinistre',
            ],
            'Dépenses' => [
                'view-expense'   => 'Voir les dépenses',
                'create-expense' => 'Créer une dépense',
                'edit-expense'   => 'Modifier une dépense',
                'delete-expense' => 'Supprimer une dépense',
            ],
            'Utilisateurs' => [
                'view-user'         => 'Voir les utilisateurs',
                'create-user'       => 'Créer un utilisateur',
                'edit-user'         => 'Modifier un utilisateur',
                'delete-user'       => 'Supprimer un utilisateur',
                'manage-user-roles' => 'Gérer les rôles utilisateur',
            ],
            'Rôles' => [
                'view-role'         => 'Voir les rôles',
                'create-role'       => 'Créer un rôle',
                'edit-role'         => 'Modifier un rôle',
                'delete-role'       => 'Supprimer un rôle',
                'assign-permission' => 'Assigner des permissions',
            ],
            'Journal' => [
                'view-logs' => "Voir le journal d'activité",
            ],
            'Facturation' => [
                'view-billing'    => 'Voir la facturation',
                'create-billing'  => 'Créer une facture',
                'edit-billing'    => 'Modifier une facture',
                'delete-billing'  => 'Supprimer une facture',
                'approve-billing' => 'Approuver une facture',
            ],
            'Paramètres' => [
                'manage-settings' => 'Gérer les paramètres',
                'view-parameter'  => 'Voir les paramètres métier',
            ],
            'Site web' => [
                'manage-website' => 'Gérer le site web',
            ],
            'Tableau de bord' => [
                'view-dashboard' => 'Voir le tableau de bord',
            ],
            'Notifications' => [
                'view-notification' => 'Voir les notifications',
            ],
            'Rapports' => [
                'view-reports' => 'Voir les rapports',
            ],
        ];

        foreach ($catalog as $module => $permissions) {
            foreach ($permissions as $name => $label) {
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'api'],
                    ['label' => $label, 'module' => $module]
                );
            }
        }
    }
}
