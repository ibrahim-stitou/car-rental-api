<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Parameter::updateOrCreate(
            ['category' => 'expense_category', 'value' => 'claim'],
            ['label' => 'Sinistre', 'sort_order' => 12, 'is_active' => true]
        );
    }

    public function down(): void
    {
        Parameter::where('category', 'expense_category')->where('value', 'claim')->forceDelete();
    }
};
