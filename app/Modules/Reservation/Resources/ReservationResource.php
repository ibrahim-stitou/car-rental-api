<?php

namespace App\Modules\Reservation\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    /**
     * Every contract PDF ever generated for this reservation — the current
     * one plus everything archived to 'contract_history' on each
     * regeneration — so the detail page can offer a downloadable trail for
     * traceability, not just the latest file.
     */
    private function contractVersions(): array
    {
        $current  = $this->getMedia('contract');
        $archived = $this->getMedia('contract_history');

        return $current->concat($archived)
            ->sortByDesc('created_at')
            ->values()
            ->map(fn($media) => [
                'id'         => $media->id,
                'url'        => $media->getUrl(),
                'file_name'  => $media->file_name,
                'created_at' => $media->created_at?->toISOString(),
                'is_current' => $media->collection_name === 'contract',
            ])
            ->all();
    }

    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'reference'           => $this->reservation_number,
            'reservation_number'  => $this->reservation_number,
            'agency'              => $this->whenLoaded('agency', fn() => [
                'id' => $this->agency->id, 'name' => $this->agency->name,
            ]),
            'vehicle'             => $this->whenLoaded('vehicle', fn() => [
                'id' => $this->vehicle->id, 'full_name' => $this->vehicle->full_name,
                'registration_number' => $this->vehicle->registration_number,
            ]),
            'client'              => $this->whenLoaded('client', fn() => [
                'id' => $this->client->id, 'full_name' => $this->client->full_name,
                'phone' => $this->client->phone,
            ]),
            'pickup_date'         => $this->pickup_date?->toISOString(),
            'return_date'         => $this->return_date?->toISOString(),
            'actual_return_date'  => $this->actual_return_date?->toISOString(),
            'pickup_location'     => $this->pickup_location,
            'return_location'     => $this->return_location,
            'actual_return_location' => $this->actual_return_location,
            'is_favorable'        => $this->is_favorable,
            'closure_comment'     => $this->closure_comment,
            'contract_generated_at' => $this->contract_generated_at?->toISOString(),
            'contract_status'     => $this->contract_status,
            'contract_events'     => $this->whenLoaded('contractEvents', fn() => $this->contractEvents->map(fn($e) => [
                'id'         => $e->id,
                'event_type' => $e->event_type,
                'reason'     => $e->reason,
                'actor'      => $e->actor ? ['id' => $e->actor->id, 'full_name' => $e->actor->full_name] : null,
                'created_at' => $e->created_at?->toISOString(),
            ])),
            'status'              => $this->status,
            'rental_unit'         => $this->rental_unit,
            'daily_rate'          => $this->daily_rate,
            'hourly_rate'         => $this->hourly_rate,
            'monthly_rate'        => $this->monthly_rate,
            'total_days'          => $this->total_days,
            'total_hours'         => $this->total_hours,
            'total_months'        => $this->total_months,
            'months_elapsed'      => $this->months_elapsed,
            'months_due'          => $this->months_due,
            'amount_due_so_far'   => $this->amount_due_so_far,
            'subtotal'            => $this->subtotal,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount'     => $this->discount_amount,
            'additional_fees'     => $this->additional_fees,
            'total_amount'        => $this->total_amount,
            'deposit_amount'      => $this->deposit_amount,
            'deposit_paid'        => $this->deposit_paid,
            'paid_amount'         => (float) (array_key_exists('payments_sum_amount', $this->getAttributes())
                ? ($this->payments_sum_amount ?? 0)
                : $this->payments()->sum('amount')),
            // LLD only — total already billed across this reservation's 'LLD'-type invoices,
            // distinct from paid_amount (which tracks money actually received).
            'invoiced_amount'     => (float) (array_key_exists('lld_billing_documents_sum_total_amount', $this->getAttributes())
                ? ($this->lld_billing_documents_sum_total_amount ?? 0)
                : $this->lldBillingDocuments()->sum('total_amount')),
            'payment_status'      => $this->payment_status,
            'payment_method'      => $this->payment_method,
            'initial_mileage'     => $this->initial_mileage,
            'final_mileage'       => $this->final_mileage,
            'fuel_level_pickup'   => $this->fuel_level_pickup,
            'fuel_level_return'   => $this->fuel_level_return,
            'is_overdue'          => $this->isOverdue(),
            'is_early_return'     => $this->is_early_return,
            'notes'               => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'contract'            => $this->getFirstMediaUrl('contract'),
            'contract_versions'   => $this->contractVersions(),
            'pickup_photos'       => $this->getMediaByCollection('pickup_photos'),
            'return_photos'       => $this->getMediaByCollection('return_photos'),
            'documents'           => $this->getMediaByCollection('documents'),
            'creator'             => $this->whenLoaded('creator', fn() => [
                'id' => $this->creator->id, 'full_name' => $this->creator->full_name,
            ]),
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}

