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
        Schema::create('web_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->uuid('vehicle_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone', 30);
            $table->dateTime('pickup_date');
            $table->dateTime('return_date');
            $table->string('pickup_location')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->uuid('reservation_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('restrict');
            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('set null');
            $table->index('status');
            $table->index('vehicle_id');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_reservations');
    }
};
