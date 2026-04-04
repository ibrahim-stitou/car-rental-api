<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reservation_number')->unique();
            $table->uuid('agency_id');
            $table->uuid('vehicle_id');
            $table->uuid('client_id');
            $table->uuid('created_by')->nullable();
            $table->dateTime('pickup_date');
            $table->dateTime('return_date');
            $table->dateTime('actual_return_date')->nullable();
            $table->string('pickup_location');
            $table->string('return_location');
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->decimal('daily_rate', 10, 2);
            $table->integer('total_days');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('additional_fees', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->boolean('deposit_paid')->default(false);
            $table->timestamp('deposit_paid_at')->nullable();
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded'])->default('pending');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'online'])->nullable();
            $table->integer('initial_mileage')->nullable();
            $table->integer('final_mileage')->nullable();
            $table->enum('fuel_level_pickup', ['empty', 'quarter', 'half', 'three_quarters', 'full'])->nullable();
            $table->enum('fuel_level_return', ['empty', 'quarter', 'half', 'three_quarters', 'full'])->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('restrict');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('restrict');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('agency_id');
            $table->index('vehicle_id');
            $table->index('client_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index(['pickup_date', 'return_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
