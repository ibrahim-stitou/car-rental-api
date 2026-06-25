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
        // Drop index that references the column before changing its type
        Schema::table('notification_log_items', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id']);
        });

        \DB::statement('ALTER TABLE notification_log_items MODIFY notifiable_id VARCHAR(191) NULL');

        Schema::table('notification_log_items', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notification_log_items', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id']);
        });

        \DB::statement('ALTER TABLE notification_log_items MODIFY notifiable_id BIGINT UNSIGNED NULL');

        Schema::table('notification_log_items', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }
};
