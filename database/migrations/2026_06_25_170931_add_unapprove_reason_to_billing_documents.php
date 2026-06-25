<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->text('unapprove_reason')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->dropColumn('unapprove_reason');
        });
    }
};
