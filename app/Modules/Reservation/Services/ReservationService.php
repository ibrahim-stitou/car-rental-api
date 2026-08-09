<?php

namespace App\Modules\Reservation\Services;

use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\Vehicle;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Reservation\Repositories\ReservationRepository;
use App\Services\PdfService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /**
     * Fields printed on the contract PDF (see resources/views/pdf/contract.blade.php).
     * A change to any of these on a reservation whose contract is currently
     * `valid` means the printed document no longer matches reality, so it
     * must be flagged `invalidated` until regenerated. Notes/mileage/fuel are
     * intentionally excluded — not billing-relevant / set at activate|complete time.
     */
    private const CONTRACT_RELEVANT_FIELDS = [
        'pickup_date', 'return_date', 'pickup_location', 'return_location',
        'daily_rate', 'discount_percentage', 'additional_fees', 'total_amount',
    ];

    public function __construct(
        protected ReservationRepository $repository,
        protected NotificationService $notificationService,
    ) {}

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['agency', 'vehicle', 'client'], function ($dataTable) {
            $dataTable->addColumn('client_name', fn($r) => $r->client?->full_name ?? '—')
                ->addColumn('vehicle_name', fn($r) => $r->vehicle?->full_name ?? '—')
                ->addColumn('agency_name', fn($r) => $r->agency?->name ?? '—')
                ->addColumn('duration', fn($r) => $r->total_days . ' jour(s)')
                ->filterColumn('client_name', function ($query, $keyword) {
                    $query->whereHas('client', function ($q) use ($keyword) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"]);
                    });
                });
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['agency', 'vehicle', 'client', 'creator'], 'created_at', 'desc', [], ['payments' => 'amount']);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($term, $this->repository->getSearchFields(), ['vehicle', 'client'], $perPage);
    }

    public function find(string $id): Reservation
    {
        return $this->repository->findByIdOrFail($id, ['agency', 'vehicle', 'client', 'creator'], ['payments' => 'amount']);
    }

    public function create(array $data): Reservation
    {
        $initialPaidAmount   = $data['initial_paid_amount'] ?? null;
        $initialPaymentMethod = $data['initial_payment_method'] ?? 'cash';
        unset($data['initial_paid_amount'], $data['initial_payment_method']);

        $data['created_by'] = auth('api')->id();
        $data['status'] = $data['status'] ?? 'pending';

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $data['daily_rate'] = $data['daily_rate'] ?? $vehicle->daily_rate;
        $data['deposit_amount'] = $data['deposit_amount'] ?? $vehicle->deposit_amount;

        $reservation = new Reservation($data);
        $reservation->calculateTotal();
        $data = array_merge($data, [
            'total_days'      => $reservation->total_days,
            'subtotal'        => $reservation->subtotal,
            'discount_amount' => $reservation->discount_amount,
            'total_amount'    => $reservation->total_amount,
        ]);

        $reservation = DB::transaction(function () use ($data, $initialPaidAmount, $initialPaymentMethod) {
            $reservation = $this->repository->create($data);

            // Bundled into reservation creation (gated only by create-reservation)
            // so it stays exempt from the manage-payment permission required for
            // any payment recorded afterwards via the dedicated payments endpoint.
            if ($initialPaidAmount !== null && $initialPaidAmount > 0) {
                ReservationPayment::create([
                    'reservation_id' => $reservation->id,
                    'recorded_by'    => auth('api')->id(),
                    'amount'         => min((float) $initialPaidAmount, (float) $reservation->total_amount),
                    'payment_method' => $initialPaymentMethod,
                    'payment_date'   => now()->toDateString(),
                    'notes'          => 'Acompte initial',
                ]);
                $reservation->syncPaymentStatus();
            }

            return $reservation;
        });

        $this->notificationService->notifyReservationCreated($reservation);

        return $reservation;
    }

    /**
     * FIX: previously this just delegated to $this->repository->update($id,
     * $data), which persists the raw fields sent by the client (dates,
     * daily_rate, discount_percentage, additional_fees, ...) but never
     * recalculates total_days/subtotal/discount_amount/total_amount, and
     * never resyncs payment_status. Any edit that touches billing-related
     * fields therefore left stale totals in the database — the edit form
     * only *looked* recalculated because that math was being redone
     * client-side, while the stored record (and every other screen reading
     * from it) kept showing the old numbers.
     *
     * Now mirrors create()/complete(): fill the changes onto the model,
     * recompute the derived billing fields from whatever pickup_date/
     * return_date/daily_rate/discount_percentage/additional_fees end up
     * on the model, persist, and resync payment_status against the
     * (possibly changed) total_amount.
     */
    public function update(string $id, array $data): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->fill($data);
        $reservation->calculateTotal();
        $reservation->save();
        $reservation->syncPaymentStatus();

        if ($reservation->contract_status === 'valid' && $reservation->wasChanged(self::CONTRACT_RELEVANT_FIELDS)) {
            $this->invalidateContract($reservation, 'Modification de la réservation');
        }

        return $reservation->fresh();
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function restore(string $id): Reservation
    {
        return $this->repository->restore($id);
    }

    public function confirm(string $id): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->update(['status' => 'confirmed']);
        $reservation = $reservation->fresh();

        $this->notificationService->notifyReservationConfirmed($reservation);

        return $reservation;
    }

    /**
     * Generate the contract PDF and mark it valid. Once contract_status is
     * no longer 'not_generated', subsequent calls are a no-op unless $force
     * is passed (used by revalidateContract() to regenerate after an
     * invalidation) — the stored file remains the source of truth until an
     * explicit regeneration is requested.
     */
    public function generateAndLockContract(Reservation $reservation, bool $force = false): Reservation
    {
        if ($reservation->contract_status !== 'not_generated' && !$force) {
            return $reservation;
        }

        $isFirstGeneration = $reservation->contract_status === 'not_generated';

        app(PdfService::class)->saveReservationContractToMedia($reservation);
        $reservation->update([
            'contract_generated_at' => $reservation->contract_generated_at ?? now(),
            'contract_status'       => 'valid',
        ]);

        $this->logContractEvent($reservation, $isFirstGeneration ? 'generated' : 'regenerated');

        return $reservation->fresh();
    }

    /**
     * Marks an already-valid contract as invalidated (no longer trustworthy
     * for printing) without touching the reservation's own workflow status
     * (pending/confirmed/active) or vehicle availability — it's a purely
     * document-level flag. No-op if the contract isn't currently valid
     * (e.g. never generated yet), so callers can invoke this unconditionally
     * after any reservation mutation.
     */
    public function invalidateContract(Reservation $reservation, string $reason): Reservation
    {
        if ($reservation->contract_status !== 'valid') {
            return $reservation;
        }

        $reservation->update(['contract_status' => 'invalidated']);
        $this->logContractEvent($reservation, 'invalidated', $reason);

        return $reservation->fresh();
    }

    /**
     * Archives the current contract file (if any) to the 'contract_history'
     * collection and generates a fresh one from the reservation's current
     * data, marking it valid again. Used both for manual re-validation and
     * for the automatic extension/completion flows.
     */
    public function revalidateContract(string $id): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);

        $current = $reservation->getFirstMedia('contract');
        if ($current) {
            $current->move($reservation, 'contract_history');
        }

        return $this->generateAndLockContract($reservation, force: true);
    }

    /**
     * After a reservation's dates change (extension), a previously-valid
     * contract no longer reflects reality. Invalidate then immediately
     * regenerate against the current data, so the flow stays coherent with
     * the manual invalidate/revalidate primitives instead of a bespoke path.
     * No-op if no contract had been generated yet — the next generation
     * will simply use the already-updated dates.
     */
    public function regenerateContractAfterExtension(Reservation $reservation): Reservation
    {
        if ($reservation->contract_status === 'not_generated') {
            return $reservation;
        }

        $this->invalidateContract($reservation, 'Prolongation de la réservation');

        return $this->revalidateContract($reservation->id);
    }

    private function logContractEvent(Reservation $reservation, string $eventType, ?string $reason = null): void
    {
        $reservation->contractEvents()->create([
            'actor_id'   => auth('api')->id(),
            'event_type' => $eventType,
            'reason'     => $reason,
            'snapshot'   => [
                'pickup_date'          => optional($reservation->pickup_date)->toIso8601String(),
                'return_date'          => optional($reservation->return_date)->toIso8601String(),
                'pickup_location'      => $reservation->pickup_location,
                'return_location'      => $reservation->return_location,
                'daily_rate'           => $reservation->daily_rate,
                'discount_percentage'  => $reservation->discount_percentage,
                'additional_fees'      => $reservation->additional_fees,
                'total_amount'         => $reservation->total_amount,
            ],
        ]);
    }

    public function activate(string $id, array $data): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->update(array_merge($data, ['status' => 'active']));
        $reservation->vehicle->update(['status' => 'rented']);
        $reservation = $reservation->fresh();

        $this->notificationService->notifyReservationActivated($reservation);

        return $reservation;
    }

    public function complete(string $id, array $data): Reservation
    {
        $finalAmountOverride = $data['final_total_amount'] ?? null;
        unset($data['final_total_amount']);

        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->fill(array_merge($data, [
            'status'             => 'completed',
            'actual_return_date' => $data['actual_return_date'] ?? now(),
        ]));

        // The agent's explicit final amount (e.g. after negotiating an early
        // return) always wins; otherwise recalculate — which also applies the
        // early-return proration and picks up any additional_fees change.
        if ($finalAmountOverride !== null) {
            $reservation->total_amount = $finalAmountOverride;
        } else {
            $reservation->calculateTotal();
        }

        $wasEarlyOrLate = $reservation->actual_return_date && $reservation->return_date
            && !$reservation->actual_return_date->equalTo($reservation->return_date);

        $reservation->save();
        $reservation->syncPaymentStatus();
        $reservation->vehicle->update(['status' => 'available']);
        $reservation = $reservation->fresh();

        // The printed contract still shows the originally planned return
        // date/total — if the actual closure data differs (early/late return,
        // manual final-amount override), regenerate it now so the document
        // handed to the client matches reality without a separate manual step.
        if ($reservation->contract_status === 'valid' && ($wasEarlyOrLate || $finalAmountOverride !== null)) {
            $this->invalidateContract($reservation, 'Clôture de la réservation (retour anticipé/tardif ou montant final ajusté)');
            $reservation = $this->revalidateContract($reservation->id);
        }

        $this->notificationService->notifyReservationCompleted($reservation);

        return $reservation;
    }

    public function cancel(string $id, ?string $reason = null): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at'        => now(),
        ]);
        if ($reservation->vehicle->status === 'rented') {
            $reservation->vehicle->update(['status' => 'available']);
        }
        $reservation = $reservation->fresh();

        $this->notificationService->notifyReservationCancelled($reservation);

        return $reservation;
    }

    public function noShow(string $id): Reservation
    {
        $reservation = $this->repository->findByIdOrFail($id);
        $reservation->update(['status' => 'no_show']);
        if ($reservation->vehicle->status === 'rented') {
            $reservation->vehicle->update(['status' => 'available']);
        }
        return $reservation->fresh();
    }

    public function overdue(int $perPage = 15): LengthAwarePaginator
    {
        return Reservation::overdue()->with(['vehicle', 'client'])->paginate($perPage);
    }

    public function calendar(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Reservation::with(['vehicle', 'client'])
            ->whereIn('status', ['confirmed', 'active', 'pending']);

        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereBetween('pickup_date', [$filters['start_date'], $filters['end_date']])
                    ->orWhereBetween('return_date', [$filters['start_date'], $filters['end_date']]);
            });
        }

        return $query->orderBy('pickup_date')->get();
    }

    public function statistics(array $filters = []): array
    {
        // Migrated reservations are a pure historical archive (forced to
        // completed/paid regardless of their real original status) and must
        // not inflate live KPIs/revenue.
        $query = Reservation::query()->whereNull('legacy_id');
        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        return [
            'total'       => $query->count(),
            'pending'     => (clone $query)->where('status', 'pending')->count(),
            'confirmed'   => (clone $query)->where('status', 'confirmed')->count(),
            'active'      => (clone $query)->where('status', 'active')->count(),
            'completed'   => (clone $query)->where('status', 'completed')->count(),
            'cancelled'   => (clone $query)->where('status', 'cancelled')->count(),
            'overdue'     => (clone $query)->where('status', 'active')->where('return_date', '<', now())->count(),
            'total_revenue' => (clone $query)->where('status', 'completed')->sum('total_amount'),
        ];
    }
}
