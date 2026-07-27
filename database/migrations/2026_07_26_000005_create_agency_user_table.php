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
        Schema::create('agency_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agency_id');
            $table->uuid('user_id');
            $table->string('stamp_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();

            $table->foreign('agency_id')->references('id')->on('agencies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['agency_id', 'user_id']);
        });

        // Backfill: every user currently linked to a single agency gets one pivot row.
        $users = DB::table('users')->whereNotNull('agency_id')->get(['id', 'agency_id']);
        $now = now();
        foreach ($users as $user) {
            DB::table('agency_user')->insert([
                'id'         => (string) Str::uuid(),
                'agency_id'  => $user->agency_id,
                'user_id'    => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_user');
    }
};
