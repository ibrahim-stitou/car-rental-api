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
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('contract_generated_at')->nullable()->after('actual_return_date');
            $table->string('actual_return_location')->nullable()->after('return_location');
            $table->boolean('is_favorable')->nullable()->after('actual_return_location');
            $table->text('closure_comment')->nullable()->after('is_favorable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['contract_generated_at', 'actual_return_location', 'is_favorable', 'closure_comment']);
        });
    }
};
