<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->uuid('expense_id')->nullable()->after('actual_cost');
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('set null');
            $table->index('expense_id');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->uuid('expense_id')->nullable()->after('company_expense_amount');
            $table->foreign('expense_id')->references('id')->on('expenses')->onDelete('set null');
            $table->index('expense_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });
    }
};
