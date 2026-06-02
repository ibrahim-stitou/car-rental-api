<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vehicle_id');
            $table->uuid('reservation_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('maintenance_id')->nullable();
            $table->uuid('created_by');

            $table->date('claim_date');
            $table->string('claim_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('agent_notes')->nullable();

            $table->enum('accident_type', [
                'collision',
                'theft',
                'vandalism',
                'natural_disaster',
                'fire',
                'glass_damage',
                'parking',
                'other',
            ])->default('collision');

            $table->boolean('is_client_responsible')->default(false);
            $table->text('responsible_notes')->nullable(); // si l'équipe, qui était responsable

            $table->enum('status', [
                'open',
                'under_review',
                'insurance_claimed',
                'settled',
                'closed',
                'rejected',
            ])->default('open');

            // Montants financiers
            $table->decimal('total_damage_amount', 10, 2)->default(0);
            $table->decimal('insurance_amount_recovered', 10, 2)->default(0);
            $table->decimal('client_paid_amount', 10, 2)->default(0);
            $table->decimal('company_expense_amount', 10, 2)->default(0);

            // Insurance reference
            $table->string('insurance_reference')->nullable();
            $table->date('insurance_claim_date')->nullable();
            $table->date('settlement_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('restrict');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('maintenance_id')->references('id')->on('maintenances')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->index('vehicle_id');
            $table->index('client_id');
            $table->index('status');
            $table->index('claim_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
