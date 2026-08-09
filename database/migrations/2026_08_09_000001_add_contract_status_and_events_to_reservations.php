<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // not_generated | valid | invalidated — independent of the reservation's
            // own status (pending/confirmed/active/...), so invalidating a contract
            // never reverts the reservation workflow itself.
            $table->string('contract_status')->default('not_generated')->after('contract_generated_at');
        });

        Schema::create('reservation_contract_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reservation_id');
            $table->string('event_type'); // generated|regenerated|invalidated
            $table->uuid('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('users')->onDelete('set null');
            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_contract_events');
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('contract_status');
        });
    }
};
