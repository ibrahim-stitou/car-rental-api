<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Second driver - can be a registered client or just free text
            $table->uuid('second_driver_id')->nullable()->after('client_id');
            $table->string('second_driver_name')->nullable()->after('second_driver_id');
            $table->string('second_driver_license')->nullable()->after('second_driver_name');
            $table->string('second_driver_phone')->nullable()->after('second_driver_license');

            // Validated by (user who confirmed/activated)
            $table->uuid('validated_by')->nullable()->after('created_by');

            $table->foreign('second_driver_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('validated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['second_driver_id']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['second_driver_id', 'second_driver_name', 'second_driver_license', 'second_driver_phone', 'validated_by']);
        });
    }
};
