<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing index (MySQL < 8.0.3 doesn't support DROP INDEX IF EXISTS)
        $indexes = DB::select("SHOW INDEXES FROM media WHERE Key_name = 'media_model_type_model_id_index'");
        if (!empty($indexes)) {
            DB::statement('ALTER TABLE `media` DROP INDEX `media_model_type_model_id_index`');
        }

        // Change model_id from BIGINT UNSIGNED to VARCHAR(36) to support UUID primary keys
        DB::statement('ALTER TABLE `media` MODIFY COLUMN `model_id` VARCHAR(36) NOT NULL');

        // Re-create composite index
        DB::statement('CREATE INDEX `media_model_type_model_id_index` ON `media` (`model_type`(100), `model_id`)');
    }

    public function down(): void
    {
        $indexes = DB::select("SHOW INDEXES FROM media WHERE Key_name = 'media_model_type_model_id_index'");
        if (!empty($indexes)) {
            DB::statement('ALTER TABLE `media` DROP INDEX `media_model_type_model_id_index`');
        }

        DB::statement('ALTER TABLE `media` MODIFY COLUMN `model_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('CREATE INDEX `media_model_type_model_id_index` ON `media` (`model_type`(100), `model_id`)');
    }
};
