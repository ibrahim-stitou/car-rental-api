<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_client', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->uuid('client_id');
            $table->timestamps();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->unique(['agency_id', 'client_id']);
        });

        // Backfill: every client currently linked to a single agency gets one pivot row.
        $clients = DB::table('clients')->whereNotNull('agency_id')->get(['id', 'agency_id']);
        $now = now();
        foreach ($clients as $client) {
            DB::table('agency_client')->insert([
                'id'         => (string) Str::uuid(),
                'agency_id'  => $client->agency_id,
                'client_id'  => $client->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_client');
    }
};
