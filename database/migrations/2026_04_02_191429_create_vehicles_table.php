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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->string('brand', 50);
            $table->string('model', 50);
            $table->integer('year');
            $table->string('registration_number')->unique();
            $table->string('vin', 17)->unique();
            $table->string('color')->nullable();
            $table->enum('category', ['economy', 'compact', 'midsize', 'suv', 'luxury', 'van']);
            $table->enum('fuel_type', ['gasoline', 'diesel', 'electric', 'hybrid']);
            $table->enum('transmission', ['manual', 'automatic']);
            $table->integer('seats')->default(5);
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->integer('mileage')->default(0);
            $table->enum('status', ['available', 'rented', 'maintenance', 'out_of_service'])->default('available');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('restrict');
            $table->index('agency_id');
            $table->index('status');
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
