<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->boolean('show_on_website')->default(false)->after('is_active');
            $table->text('website_description')->nullable()->after('show_on_website');
            $table->decimal('website_price_override', 10, 2)->nullable()->after('website_description');
            $table->index('show_on_website');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['show_on_website']);
            $table->dropColumn(['show_on_website', 'website_description', 'website_price_override']);
        });
    }
};
