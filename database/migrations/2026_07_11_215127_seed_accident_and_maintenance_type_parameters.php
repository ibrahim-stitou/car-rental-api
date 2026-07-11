<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $accidentTypes = [
            'collision'         => 'Collision',
            'theft'             => 'Vol',
            'vandalism'         => 'Vandalisme',
            'natural_disaster'  => 'Catastrophe naturelle',
            'fire'              => 'Incendie',
            'glass_damage'      => 'Bris de glace',
            'parking'           => 'Accrochage au stationnement',
            'other'             => 'Autre',
        ];

        $maintenanceTypes = [
            'oil_change'    => 'Vidange',
            'tire_change'   => 'Changement de pneus',
            'brake_service' => 'Freins',
            'engine_repair' => 'Réparation moteur',
            'body_repair'   => 'Carrosserie',
            'electrical'    => 'Électrique',
            'cleaning'      => 'Nettoyage',
            'other'         => 'Autre',
        ];

        $maintenanceSubTypes = [
            'oil_change'       => 'Vidange',
            'tire_change'      => 'Changement de pneus',
            'brake_service'    => 'Freins',
            'filter_change'    => 'Changement de filtre',
            'battery'          => 'Batterie',
            'timing_belt'      => 'Courroie de distribution',
            'general_service'  => 'Entretien général',
            'other'            => 'Autre',
        ];

        $rows = [];
        foreach ($accidentTypes as $value => $label) {
            $rows[] = ['category' => 'accident_type', 'value' => $value, 'label' => $label];
        }
        foreach ($maintenanceTypes as $value => $label) {
            $rows[] = ['category' => 'maintenance_type', 'value' => $value, 'label' => $label];
        }
        foreach ($maintenanceSubTypes as $value => $label) {
            $rows[] = ['category' => 'maintenance_sub_type', 'value' => $value, 'label' => $label];
        }

        foreach ($rows as $i => $row) {
            Parameter::updateOrCreate(
                ['category' => $row['category'], 'value' => $row['value']],
                ['label' => $row['label'], 'sort_order' => $i, 'is_active' => true]
            );
        }
    }

    public function down(): void
    {
        Parameter::whereIn('category', ['accident_type', 'maintenance_type', 'maintenance_sub_type'])->forceDelete();
    }
};
