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
            $table->enum('client_type', ['physical', 'moral'])->default('physical')->after('id');

            // Société (personne morale)
            $table->string('company_name')->nullable()->after('last_name');
            $table->string('company_type')->nullable()->after('company_name');
            $table->string('company_phone')->nullable()->after('company_type');
            $table->string('company_email')->nullable()->after('company_phone');
            $table->string('company_ice')->nullable()->after('company_email');
            $table->text('company_address')->nullable()->after('company_ice');
            $table->string('company_city')->nullable()->after('company_address');
            $table->string('company_country')->nullable()->after('company_city');

            // Infos bancaires
            $table->string('bank_name')->nullable()->after('company_country');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->text('bank_address')->nullable()->after('bank_account_number');

            $table->index('client_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['client_type']);
            $table->dropColumn([
                'client_type',
                'company_name', 'company_type', 'company_phone', 'company_email', 'company_ice',
                'company_address', 'company_city', 'company_country',
                'bank_name', 'bank_account_name', 'bank_account_number', 'bank_address',
            ]);
        });
    }
};