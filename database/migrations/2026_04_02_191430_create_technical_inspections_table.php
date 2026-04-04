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
        Schema::create('technical_inspections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vehicle_id');
            $table->date('inspection_date');
            $table->date('expiry_date');
            $table->enum('result', ['passed', 'failed', 'pending'])->default('pending');
            $table->string('inspection_center');
            $table->string('inspector_name')->nullable();
            $table->text('observations')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->date('next_inspection_date')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('vehicle_id');
            $table->index('expiry_date');
            $table->index('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_inspections');
    }
};
