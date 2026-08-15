<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a reservation payment optionally settle a specific LLD invoice
     * (chosen by the agent in the payment dialog) instead of just paying
     * down the reservation's general balance — so the invoice detail page
     * can show it as paid with the matching payment info.
     */
    public function up(): void
    {
        Schema::table('reservation_payments', function (Blueprint $table) {
            $table->uuid('billing_document_id')->nullable()->after('reservation_id');
            $table->foreign('billing_document_id')->references('id')->on('billing_documents')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_payments', function (Blueprint $table) {
            $table->dropForeign(['billing_document_id']);
            $table->dropColumn('billing_document_id');
        });
    }
};
