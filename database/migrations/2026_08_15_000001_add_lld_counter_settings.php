<?php

use App\Models\BillingDocument;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The 'LLD' document type was added after the 'counters' settings group
     * was originally seeded, so no lld_prefix/lld_separator/lld_digits/
     * lld_current rows ever existed. Setting::get() falls back to sane
     * defaults when a row is missing, so generateDocumentNumber() never
     * crashed — but the "increment counter" step is a plain update() on a
     * non-existent row (a silent no-op), so every LLD invoice kept getting
     * assigned the same document_number ("LLD-000001"), and approving a
     * second one hit the unique constraint on billing_documents.document_number.
     * This backfills the missing rows so the counter actually persists —
     * seeded from the highest LLD sequence number already in use (not 0),
     * since some installs already have a real "LLD-000001" approved before
     * this fix, and starting back at 0 would collide with it immediately.
     */
    public function up(): void
    {
        $maxSeq = BillingDocument::where('type', 'LLD')
            ->where('document_number', 'like', 'LLD-%')
            ->pluck('document_number')
            ->map(fn($n) => (int) Str::afterLast($n, '-'))
            ->max() ?? 0;

        $rows = [
            ['group' => 'counters', 'key' => 'lld_prefix',    'type' => 'string',  'label' => 'LLD · Préfixe',      'value' => 'LLD'],
            ['group' => 'counters', 'key' => 'lld_separator', 'type' => 'string',  'label' => 'LLD · Séparateur',   'value' => '-'],
            ['group' => 'counters', 'key' => 'lld_digits',    'type' => 'integer', 'label' => 'LLD · Chiffres',     'value' => '6'],
            ['group' => 'counters', 'key' => 'lld_current',   'type' => 'integer', 'label' => 'LLD · Compteur',     'value' => (string) $maxSeq],
        ];

        foreach ($rows as $row) {
            Setting::updateOrCreate(
                ['group' => $row['group'], 'key' => $row['key']],
                $row
            );
        }
    }

    public function down(): void
    {
        Setting::where('group', 'counters')
            ->whereIn('key', ['lld_prefix', 'lld_separator', 'lld_digits', 'lld_current'])
            ->delete();
    }
};
