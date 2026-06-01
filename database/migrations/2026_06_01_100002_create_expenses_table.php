<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id')->nullable();
            $table->uuid('vehicle_id')->nullable();
            $table->uuid('recorded_by')->nullable();
            $table->string('title');
            $table->enum('category', [
                'fuel', 'maintenance', 'insurance', 'vignette', 'inspection',
                'repair', 'cleaning', 'administrative', 'salary', 'rent', 'utilities', 'other',
            ]);
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'check', 'online'])->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('set null');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            $table->index('agency_id');
            $table->index('vehicle_id');
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};