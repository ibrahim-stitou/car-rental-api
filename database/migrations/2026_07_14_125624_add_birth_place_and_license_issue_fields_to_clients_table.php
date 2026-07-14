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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('birth_place')->nullable()->after('date_of_birth');
            $table->date('license_issue_date')->nullable()->after('driving_license_expiry');
            $table->string('license_issue_place')->nullable()->after('license_issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'license_issue_date', 'license_issue_place']);
        });
    }
};
