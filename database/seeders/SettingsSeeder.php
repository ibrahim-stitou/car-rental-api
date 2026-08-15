<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // ─── Informations société ──────────────────────────────────────────
            ['group' => 'company', 'key' => 'name',          'type' => 'string',  'label' => 'Nom de la société',          'value' => 'GES Cars'],
            ['group' => 'company', 'key' => 'tagline',        'type' => 'string',  'label' => 'Slogan',                     'value' => 'Location de véhicules à votre service'],
            ['group' => 'company', 'key' => 'address',        'type' => 'string',  'label' => 'Adresse',                    'value' => ''],
            ['group' => 'company', 'key' => 'city',           'type' => 'string',  'label' => 'Ville',                      'value' => ''],
            ['group' => 'company', 'key' => 'country',        'type' => 'string',  'label' => 'Pays',                       'value' => 'Maroc'],
            ['group' => 'company', 'key' => 'postal_code',    'type' => 'string',  'label' => 'Code postal',                'value' => ''],
            ['group' => 'company', 'key' => 'phone',          'type' => 'string',  'label' => 'Téléphone principal',        'value' => ''],
            ['group' => 'company', 'key' => 'phone2',         'type' => 'string',  'label' => 'Téléphone secondaire',       'value' => ''],
            ['group' => 'company', 'key' => 'email',          'type' => 'string',  'label' => 'Email',                      'value' => ''],
            ['group' => 'company', 'key' => 'website',        'type' => 'string',  'label' => 'Site web',                   'value' => ''],
            ['group' => 'company', 'key' => 'logo_url',       'type' => 'string',  'label' => 'Logo',                       'value' => ''],
            // Identifiants fiscaux (Maroc)
            ['group' => 'company', 'key' => 'ice',            'type' => 'string',  'label' => 'ICE',                        'value' => ''],
            ['group' => 'company', 'key' => 'rc',             'type' => 'string',  'label' => 'Registre de commerce',       'value' => ''],
            ['group' => 'company', 'key' => 'cnss',           'type' => 'string',  'label' => 'CNSS',                       'value' => ''],
            ['group' => 'company', 'key' => 'if',             'type' => 'string',  'label' => 'Identifiant fiscal',         'value' => ''],
            ['group' => 'company', 'key' => 'patent',         'type' => 'string',  'label' => 'Numéro de patente',          'value' => ''],
            ['group' => 'company', 'key' => 'capital',        'type' => 'string',  'label' => 'Capital social',             'value' => ''],
            ['group' => 'company', 'key' => 'company_type',   'type' => 'string',  'label' => 'Forme juridique',            'value' => ''],
            // Banque
            ['group' => 'company', 'key' => 'bank_name',      'type' => 'string',  'label' => 'Nom de la banque',           'value' => ''],
            ['group' => 'company', 'key' => 'bank_account',   'type' => 'string',  'label' => 'Numéro de compte',           'value' => ''],
            ['group' => 'company', 'key' => 'bank_rib',       'type' => 'string',  'label' => 'RIB',                        'value' => ''],
            ['group' => 'company', 'key' => 'bank_swift',     'type' => 'string',  'label' => 'Code SWIFT',                 'value' => ''],

            // ─── Paramètres de facturation ─────────────────────────────────────
            ['group' => 'billing', 'key' => 'currency',         'type' => 'string',  'label' => 'Devise',                        'value' => 'MAD'],
            ['group' => 'billing', 'key' => 'currency_symbol',  'type' => 'string',  'label' => 'Symbole devise',                 'value' => 'DH'],
            ['group' => 'billing', 'key' => 'tax_rate',         'type' => 'integer', 'label' => 'Taux TVA (%)',                   'value' => '20'],
            ['group' => 'billing', 'key' => 'tax_label',        'type' => 'string',  'label' => 'Libellé taxe',                   'value' => 'TVA'],
            ['group' => 'billing', 'key' => 'payment_terms',    'type' => 'integer', 'label' => 'Délai de paiement (jours)',       'value' => '30'],
            ['group' => 'billing', 'key' => 'invoice_prefix',   'type' => 'string',  'label' => 'Préfixe facture',                'value' => 'FA-'],
            ['group' => 'billing', 'key' => 'quote_prefix',     'type' => 'string',  'label' => 'Préfixe devis',                  'value' => 'DV-'],
            ['group' => 'billing', 'key' => 'invoice_footer',   'type' => 'string',  'label' => 'Pied de page facture',           'value' => 'Merci pour votre confiance.'],

            // ─── Seuils des alertes ────────────────────────────────────────────
            ['group' => 'notifications', 'key' => 'insurance_alert_days',       'type' => 'integer', 'label' => 'Alerte assurance (jours avant expiration)',    'value' => '30'],
            ['group' => 'notifications', 'key' => 'inspection_alert_days',      'type' => 'integer', 'label' => 'Alerte visite technique (jours)',              'value' => '30'],
            ['group' => 'notifications', 'key' => 'vignette_alert_days',        'type' => 'integer', 'label' => 'Alerte vignette (jours)',                      'value' => '30'],
            ['group' => 'notifications', 'key' => 'maintenance_alert_days',     'type' => 'integer', 'label' => 'Alerte maintenance (jours)',                    'value' => '7'],
            ['group' => 'notifications', 'key' => 'reservation_reminder_hours', 'type' => 'integer', 'label' => 'Rappel réservation (heures avant)',             'value' => '24'],
            ['group' => 'notifications', 'key' => 'billing_overdue_days',       'type' => 'integer', 'label' => 'Alerte facture impayée (jours)',               'value' => '30'],
            ['group' => 'notifications', 'key' => 'client_inactive_days',       'type' => 'integer', 'label' => 'Alerte client inactif (jours)',                'value' => '60'],

            // ─── Paramètres de réservation ─────────────────────────────────────
            ['group' => 'reservation', 'key' => 'default_deposit_percent',    'type' => 'integer', 'label' => 'Acompte par défaut (%)',              'value' => '30'],
            ['group' => 'reservation', 'key' => 'late_return_fee_per_hour',   'type' => 'integer', 'label' => 'Frais retard par heure (DH)',          'value' => '50'],
            ['group' => 'reservation', 'key' => 'cancellation_free_hours',    'type' => 'integer', 'label' => 'Annulation gratuite avant (heures)',   'value' => '24'],
            ['group' => 'reservation', 'key' => 'min_rental_hours',           'type' => 'integer', 'label' => 'Durée minimum de location (heures)',   'value' => '24'],
            ['group' => 'reservation', 'key' => 'max_rental_days',            'type' => 'integer', 'label' => 'Durée maximum de location (jours)',    'value' => '90'],

            // ─── Paramètres du site web ────────────────────────────────────────
            ['group' => 'website', 'key' => 'title',               'type' => 'string',  'label' => 'Titre du site',              'value' => 'GES Cars — Location de Véhicules'],
            ['group' => 'website', 'key' => 'subtitle',            'type' => 'string',  'label' => 'Sous-titre',                 'value' => 'Trouvez le véhicule idéal pour vos déplacements'],
            ['group' => 'website', 'key' => 'hero_description',    'type' => 'string',  'label' => 'Description section hero',   'value' => 'Service de location de voitures professionnel, simple et rapide.'],
            ['group' => 'website', 'key' => 'hero_image_url',      'type' => 'string',  'label' => 'Image de fond hero',         'value' => ''],
            ['group' => 'website', 'key' => 'about_title',         'type' => 'string',  'label' => 'Titre "À propos"',           'value' => 'À Propos de Nous'],
            ['group' => 'website', 'key' => 'about_description',   'type' => 'string',  'label' => 'Description "À propos"',     'value' => ''],
            ['group' => 'website', 'key' => 'contact_email',       'type' => 'string',  'label' => 'Email de contact public',    'value' => ''],
            ['group' => 'website', 'key' => 'contact_phone',       'type' => 'string',  'label' => 'Téléphone de contact',       'value' => ''],
            ['group' => 'website', 'key' => 'contact_address',     'type' => 'string',  'label' => 'Adresse affichée',           'value' => ''],
            ['group' => 'website', 'key' => 'facebook_url',        'type' => 'string',  'label' => 'URL Facebook',               'value' => ''],
            ['group' => 'website', 'key' => 'instagram_url',       'type' => 'string',  'label' => 'URL Instagram',              'value' => ''],
            ['group' => 'website', 'key' => 'twitter_url',         'type' => 'string',  'label' => 'URL Twitter/X',              'value' => ''],
            ['group' => 'website', 'key' => 'whatsapp_number',     'type' => 'string',  'label' => 'Numéro WhatsApp',            'value' => ''],
            ['group' => 'website', 'key' => 'google_maps_embed',   'type' => 'string',  'label' => 'Code embed Google Maps',     'value' => ''],
            ['group' => 'website', 'key' => 'meta_description',    'type' => 'string',  'label' => 'Meta description SEO',       'value' => ''],
            ['group' => 'website', 'key' => 'meta_keywords',       'type' => 'string',  'label' => 'Meta keywords SEO',          'value' => ''],
            ['group' => 'website', 'key' => 'primary_color',       'type' => 'string',  'label' => 'Couleur principale',         'value' => '#1e3a5f'],
            ['group' => 'website', 'key' => 'booking_enabled',     'type' => 'boolean', 'label' => 'Activer réservations en ligne', 'value' => 'true'],
            ['group' => 'website', 'key' => 'show_prices',         'type' => 'boolean', 'label' => 'Afficher les prix sur le site',  'value' => 'true'],

            // ─── Compteurs de séquence (configuration individuelle par type) ──────
            // Facture (FA)
            ['group' => 'counters', 'key' => 'fa_prefix',    'type' => 'string',  'label' => 'FA · Préfixe',      'value' => 'FA'],
            ['group' => 'counters', 'key' => 'fa_separator', 'type' => 'string',  'label' => 'FA · Séparateur',   'value' => '-'],
            ['group' => 'counters', 'key' => 'fa_digits',    'type' => 'integer', 'label' => 'FA · Chiffres',     'value' => '6'],
            ['group' => 'counters', 'key' => 'fa_current',   'type' => 'integer', 'label' => 'FA · Compteur',     'value' => '0'],
            // Avoir (AV)
            ['group' => 'counters', 'key' => 'av_prefix',    'type' => 'string',  'label' => 'AV · Préfixe',      'value' => 'AV'],
            ['group' => 'counters', 'key' => 'av_separator', 'type' => 'string',  'label' => 'AV · Séparateur',   'value' => '-'],
            ['group' => 'counters', 'key' => 'av_digits',    'type' => 'integer', 'label' => 'AV · Chiffres',     'value' => '6'],
            ['group' => 'counters', 'key' => 'av_current',   'type' => 'integer', 'label' => 'AV · Compteur',     'value' => '0'],
            // Devis (DV)
            ['group' => 'counters', 'key' => 'dv_prefix',    'type' => 'string',  'label' => 'DV · Préfixe',      'value' => 'DV'],
            ['group' => 'counters', 'key' => 'dv_separator', 'type' => 'string',  'label' => 'DV · Séparateur',   'value' => '-'],
            ['group' => 'counters', 'key' => 'dv_digits',    'type' => 'integer', 'label' => 'DV · Chiffres',     'value' => '6'],
            ['group' => 'counters', 'key' => 'dv_current',   'type' => 'integer', 'label' => 'DV · Compteur',     'value' => '0'],
            // Bon de Commande (BC)
            ['group' => 'counters', 'key' => 'bc_prefix',    'type' => 'string',  'label' => 'BC · Préfixe',      'value' => 'BC'],
            ['group' => 'counters', 'key' => 'bc_separator', 'type' => 'string',  'label' => 'BC · Séparateur',   'value' => '-'],
            ['group' => 'counters', 'key' => 'bc_digits',    'type' => 'integer', 'label' => 'BC · Chiffres',     'value' => '6'],
            ['group' => 'counters', 'key' => 'bc_current',   'type' => 'integer', 'label' => 'BC · Compteur',     'value' => '0'],
            // Bon de Livraison (BL)
            ['group' => 'counters', 'key' => 'bl_prefix',    'type' => 'string',  'label' => 'BL · Préfixe',      'value' => 'BL'],
            ['group' => 'counters', 'key' => 'bl_separator', 'type' => 'string',  'label' => 'BL · Séparateur',   'value' => '-'],
            ['group' => 'counters', 'key' => 'bl_digits',    'type' => 'integer', 'label' => 'BL · Chiffres',     'value' => '6'],
            ['group' => 'counters', 'key' => 'bl_current',   'type' => 'integer', 'label' => 'BL · Compteur',     'value' => '0'],
            // Bon de Réception (BR)
            ['group' => 'counters', 'key' => 'br_prefix',    'type' => 'string',  'label' => 'BR · Préfixe',      'value' => 'BR'],
            ['group' => 'counters', 'key' => 'br_separator', 'type' => 'string',  'label' => 'BR · Séparateur',   'value' => '-'],
            ['group' => 'counters', 'key' => 'br_digits',    'type' => 'integer', 'label' => 'BR · Chiffres',     'value' => '6'],
            ['group' => 'counters', 'key' => 'br_current',   'type' => 'integer', 'label' => 'BR · Compteur',     'value' => '0'],
            // Facture LLD
            ['group' => 'counters', 'key' => 'lld_prefix',    'type' => 'string',  'label' => 'LLD · Préfixe',     'value' => 'LLD'],
            ['group' => 'counters', 'key' => 'lld_separator', 'type' => 'string',  'label' => 'LLD · Séparateur',  'value' => '-'],
            ['group' => 'counters', 'key' => 'lld_digits',    'type' => 'integer', 'label' => 'LLD · Chiffres',    'value' => '6'],
            ['group' => 'counters', 'key' => 'lld_current',   'type' => 'integer', 'label' => 'LLD · Compteur',    'value' => '0'],
            // Réservation
            ['group' => 'counters', 'key' => 'reservation_prefix',    'type' => 'string',  'label' => 'RES · Préfixe',    'value' => 'RES'],
            ['group' => 'counters', 'key' => 'reservation_separator', 'type' => 'string',  'label' => 'RES · Séparateur', 'value' => '-'],
            ['group' => 'counters', 'key' => 'reservation_digits',    'type' => 'integer', 'label' => 'RES · Chiffres',   'value' => '6'],
            ['group' => 'counters', 'key' => 'reservation_current',   'type' => 'integer', 'label' => 'RES · Compteur',   'value' => '0'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }

        // Clean up obsolete global counter keys
        Setting::where('group', 'counters')
            ->whereIn('key', ['separator', 'include_year', 'digits'])
            ->delete();
    }
}
