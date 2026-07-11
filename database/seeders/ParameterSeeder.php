<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];

        $insuranceTypes = [
            'third_party'   => 'Responsabilité civile',
            'comprehensive' => 'Tous risques',
            'all_risk'      => 'Tous risques étendu',
        ];
        foreach ($insuranceTypes as $value => $label) {
            $rows[] = ['category' => 'insurance_type', 'value' => $value, 'label' => $label];
        }

        $insuranceCompanies = ['Wafa Assurance', 'RMA', 'Saham Assurance', 'Atlanta', 'Sanad'];
        foreach ($insuranceCompanies as $name) {
            $rows[] = ['category' => 'insurance_company', 'value' => $name, 'label' => $name];
        }

        $expenseCategories = [
            'fuel'           => 'Carburant',
            'maintenance'    => 'Maintenance',
            'insurance'      => 'Assurance',
            'vignette'       => 'Vignette',
            'inspection'     => 'Contrôle technique',
            'repair'         => 'Réparation',
            'cleaning'       => 'Nettoyage',
            'administrative' => 'Administratif',
            'salary'         => 'Salaire',
            'rent'           => 'Loyer',
            'utilities'      => 'Charges',
            'other'          => 'Autre',
        ];
        foreach ($expenseCategories as $value => $label) {
            $rows[] = ['category' => 'expense_category', 'value' => $value, 'label' => $label];
        }

        foreach ($rows as $i => $row) {
            Parameter::updateOrCreate(
                ['category' => $row['category'], 'value' => $row['value']],
                ['label' => $row['label'], 'sort_order' => $i, 'is_active' => true]
            );
        }
    }
}
