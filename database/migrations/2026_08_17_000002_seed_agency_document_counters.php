<?php

use App\Models\Agency;
use App\Models\BillingDocument;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TYPES = ['fa', 'av', 'dv', 'bc', 'bl', 'br', 'lld'];

    /**
     * Seeds one counter row per (agency, document type), starting `current`
     * from the highest sequence number THAT agency has already issued for
     * that type (not 0, and not the old global counter's value) — so no
     * agency's very next document collides with one of its own already-
     * printed numbers. Prefix/separator/digits are copied from the old
     * global 'counters' Settings group as the starting configuration, kept
     * editable per-agency afterwards.
     */
    public function up(): void
    {
        $agencies = Agency::all(['id']);

        foreach (self::TYPES as $type) {
            $prefix    = Setting::get('counters', $type . '_prefix', strtoupper($type));
            $separator = Setting::get('counters', $type . '_separator', '-');
            $digits    = (int) Setting::get('counters', $type . '_digits', 6);

            foreach ($agencies as $agency) {
                $maxSeq = BillingDocument::where('agency_id', $agency->id)
                    ->where('type', strtoupper($type))
                    ->where('document_number', 'like', $prefix . '%')
                    ->pluck('document_number')
                    ->map(fn($n) => (int) Str::afterLast($n, $separator ?: '-'))
                    ->max() ?? 0;

                DB::table('agency_document_counters')->updateOrInsert(
                    ['agency_id' => $agency->id, 'document_type' => $type],
                    [
                        'id'         => (string) Str::uuid(),
                        'prefix'     => $prefix,
                        'separator'  => $separator,
                        'digits'     => $digits,
                        'current'    => $maxSeq,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        DB::table('agency_document_counters')->truncate();
    }
};
