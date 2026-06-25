<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->date('date_of_birth')->nullable()->change();
            $table->string('id_number')->nullable()->change();
            $table->date('id_expiry_date')->nullable()->change();
            $table->string('driving_license_number')->nullable()->change();
            $table->date('driving_license_expiry')->nullable()->change();
        });

        // id_type enum needs to allow NULL — recreate as nullable
        \DB::statement("ALTER TABLE clients MODIFY id_type ENUM('cin','passport','residence_permit') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->date('date_of_birth')->nullable(false)->change();
            $table->string('id_number')->nullable(false)->change();
            $table->date('id_expiry_date')->nullable(false)->change();
            $table->string('driving_license_number')->nullable(false)->change();
            $table->date('driving_license_expiry')->nullable(false)->change();
        });

        \DB::statement("ALTER TABLE clients MODIFY id_type ENUM('cin','passport','residence_permit') NOT NULL");
    }
};
