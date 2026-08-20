<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AgencyDocumentCounter extends Model
{
    use HasUuid;

    protected $fillable = ['agency_id', 'document_type', 'prefix', 'separator', 'digits', 'current'];

    protected function casts(): array
    {
        return [
            'digits'  => 'integer',
            'current' => 'integer',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Atomically bumps this counter and returns the formatted next document
     * number (e.g. "FA-000002"). Locked for update so two documents from the
     * same agency approved concurrently can never be handed the same number.
     */
    public static function nextNumber(string $agencyId, string $type): string
    {
        return DB::transaction(function () use ($agencyId, $type) {
            $counter = self::where('agency_id', $agencyId)
                ->where('document_type', $type)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $counter = self::create([
                    'agency_id'     => $agencyId,
                    'document_type' => $type,
                    'prefix'        => strtoupper($type),
                    'separator'     => '-',
                    'digits'        => 6,
                    'current'       => 0,
                ]);
            }

            $next = $counter->current + 1;
            $counter->update(['current' => $next]);

            return $counter->prefix . $counter->separator . str_pad((string) $next, $counter->digits, '0', STR_PAD_LEFT);
        });
    }
}
