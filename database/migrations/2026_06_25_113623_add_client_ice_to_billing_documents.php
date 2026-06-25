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
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->string('client_ice')->nullable()->after('client_email');
        });
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->dropColumn('client_ice');
        });
    }
};
