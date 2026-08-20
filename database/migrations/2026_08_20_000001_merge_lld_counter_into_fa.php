<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * LLD is conceptually just an invoice ("une facture") — it now shares
     * FA's counter entirely (same prefix, same running sequence) instead of
     * having its own "LLD-xxxxxx" series. For each agency (and for the
     * shared/global config), FA's `current` is bumped to the higher of the
     * two so numbering never steps backward past a number either series
     * already issued. The now-redundant lld rows are removed —
     * BillingDocument::generateDocumentNumber() maps type 'LLD' to the 'fa'
     * counter going forward, so a separate lld row would just be dead data.
     */
    public function up(): void
    {
        $agencyIds = DB::table('agency_document_counters')
            ->whereIn('document_type', ['fa', 'lld'])
            ->distinct()->pluck('agency_id');

        foreach ($agencyIds as $agencyId) {
            $fa  = DB::table('agency_document_counters')->where('agency_id', $agencyId)->where('document_type', 'fa')->first();
            $lld = DB::table('agency_document_counters')->where('agency_id', $agencyId)->where('document_type', 'lld')->first();
            $merged = max($fa->current ?? 0, $lld->current ?? 0);

            DB::table('agency_document_counters')->updateOrInsert(
                ['agency_id' => $agencyId, 'document_type' => 'fa'],
                [
                    'id'         => $fa->id ?? (string) \Illuminate\Support\Str::uuid(),
                    'prefix'     => $fa->prefix ?? 'FA',
                    'separator'  => $fa->separator ?? '-',
                    'digits'     => $fa->digits ?? 6,
                    'current'    => $merged,
                    'updated_at' => now(),
                    'created_at' => $fa->created_at ?? now(),
                ]
            );
        }

        DB::table('agency_document_counters')->where('document_type', 'lld')->delete();

        $faConfig  = DB::table('counter_type_settings')->where('document_type', 'fa')->first();
        $lldConfig = DB::table('counter_type_settings')->where('document_type', 'lld')->first();
        if ($faConfig || $lldConfig) {
            DB::table('counter_type_settings')->where('document_type', 'fa')->update([
                'current'    => max($faConfig->current ?? 0, $lldConfig->current ?? 0),
                'updated_at' => now(),
            ]);
        }
        DB::table('counter_type_settings')->where('document_type', 'lld')->delete();
    }

    public function down(): void
    {
        // Not meaningfully reversible — the merged sequence can't be split
        // back into two independent ones without guessing which numbers
        // "belonged" to which type.
    }
};
