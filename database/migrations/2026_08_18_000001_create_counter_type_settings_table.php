<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-document-type toggle: when `shared` is true, every agency uses
     * this ONE row's prefix/separator/digits/current instead of its own row
     * in agency_document_counters — a single number space shared across all
     * agencies for that type (e.g. reservations, by default). When false,
     * each agency keeps its own independent counter as usual. The user can
     * flip this per type at any time; it isn't fixed at setup.
     */
    public function up(): void
    {
        Schema::create('counter_type_settings', function (Blueprint $table) {
            $table->string('document_type', 15)->primary(); // fa, av, dv, bc, bl, br, lld, reservation
            $table->boolean('shared')->default(false);
            $table->string('prefix');
            $table->string('separator', 5)->default('-');
            $table->unsignedTinyInteger('digits')->default(6);
            $table->unsignedInteger('current')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_type_settings');
    }
};
