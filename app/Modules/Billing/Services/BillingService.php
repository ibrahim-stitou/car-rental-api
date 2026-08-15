<?php

namespace App\Modules\Billing\Services;

use App\Models\BillingDocument;
use App\Models\BillingDocumentItem;
use App\Modules\Billing\Repositories\BillingRepository;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(
        protected BillingRepository $repository,
        protected NotificationService $notificationService,
    ) {}

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['agency', 'reservation', 'client', 'creator'], function ($dataTable) {
            $dataTable->addColumn('type_name', fn($doc) => $doc->type_name)
                ->addColumn('agency_name', fn($doc) => $doc->agency?->name ?? '—')
                ->addColumn('is_paid_label', fn($doc) => $doc->is_paid ? 'Oui' : 'Non')
                ->addColumn('balance_formatted', fn($doc) => number_format($doc->balance, 2) . ' DH');
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['agency', 'reservation', 'client', 'creator']);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($term, $this->repository->getSearchFields(), ['agency', 'client'], $perPage);
    }

    public function find(string $id): BillingDocument
    {
        return $this->repository->findByIdOrFail($id, ['agency', 'reservation', 'client', 'creator', 'approver', 'items']);
    }

    public function create(array $data): BillingDocument
    {
        return DB::transaction(function () use ($data) {
            $data['created_by'] = auth('api')->id();

            $items = $data['items'] ?? [];
            unset($data['items']);

            $document = $this->repository->create($data);

            foreach ($items as $itemData) {
                $qty   = (int)   ($itemData['quantity']  ?? 1);
                $price = (float) ($itemData['unit_price'] ?? 0);
                BillingDocumentItem::create([
                    'billing_document_id' => $document->id,
                    'description'         => $itemData['description'],
                    'quantity'            => $qty,
                    'unit'                => $itemData['unit'] ?? null,
                    'unit_price'          => $price,
                    'total_price'         => $qty * $price,
                    'tax_rate'            => $itemData['tax_rate'] ?? 0,
                    'notes'               => $itemData['notes'] ?? null,
                ]);
            }

            $document->calculateTotals();
            $document->save();

            $document = $document->fresh(['items']);

            $this->notificationService->notifyBillingCreated($document);

            return $document;
        });
    }

    public function update(string $id, array $data): BillingDocument
    {
        return DB::transaction(function () use ($id, $data) {
            $document = $this->repository->findByIdOrFail($id);

            if ($document->status !== 'draft') {
                abort(422, 'Seuls les documents en brouillon peuvent être modifiés.');
            }

            $items = $data['items'] ?? null;
            unset($data['items']);

            $document->update($data);

            // Update items if provided
            if ($items !== null) {
                $document->items()->delete();
                foreach ($items as $itemData) {
                    $qty   = (int)   ($itemData['quantity']  ?? 1);
                    $price = (float) ($itemData['unit_price'] ?? 0);
                    BillingDocumentItem::create([
                        'billing_document_id' => $document->id,
                        'description'         => $itemData['description'],
                        'quantity'            => $qty,
                        'unit'                => $itemData['unit'] ?? null,
                        'unit_price'          => $price,
                        'total_price'         => $qty * $price,
                        'tax_rate'            => $itemData['tax_rate'] ?? 0,
                        'notes'               => $itemData['notes'] ?? null,
                    ]);
                }
            }

            $document->calculateTotals();
            $document->save();

            return $document->fresh(['items']);
        });
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function restore(string $id): BillingDocument
    {
        return $this->repository->restore($id);
    }

    public function approve(string $id): BillingDocument
    {
        $document = $this->repository->findByIdOrFail($id);

        // Generate the definitive sequential reference on first approval
        $realNumber = $document->generateDocumentNumber();

        $document->update([
            'document_number' => $realNumber,
            'status'          => 'approved',
            'approved_by'     => auth('api')->id(),
            'approved_at'     => now(),
        ]);
        $document = $document->fresh();

        $this->notificationService->notifyBillingApproved($document);

        return $document;
    }

    public function unapprove(string $id, string $reason): BillingDocument
    {
        $document = $this->repository->findByIdOrFail($id);

        if (!in_array($document->status, ['approved', 'pending'])) {
            abort(422, 'Seul un document approuvé peut être dévalidé.');
        }

        $document->update([
            'status'           => 'draft',
            'document_number'  => 'BROUILLON-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'unapprove_reason' => $reason,
            'approved_by'      => null,
            'approved_at'      => null,
        ]);

        return $document->fresh();
    }

    public function history(string $id): array
    {
        $document = $this->repository->findByIdOrFail($id);
        $audits   = $document->audits()->with('user')->orderBy('created_at', 'asc')->get();

        $eventLabels = [
            'created' => 'Création du document',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'restored'=> 'Restauration',
        ];

        return $audits->map(function ($audit) use ($eventLabels, $document) {
            $label  = $eventLabels[$audit->event] ?? ucfirst($audit->event);
            $detail = null;
            $type   = 'default';

            $new = $audit->new_values ?? [];
            $old = $audit->old_values ?? [];

            // Identify meaningful state transitions
            if (isset($new['status'])) {
                switch ($new['status']) {
                    case 'approved':
                        $label  = 'Validation du document';
                        $detail = isset($new['document_number']) ? 'Référence attribuée : ' . $new['document_number'] : null;
                        $type   = 'success';
                        break;
                    case 'draft':
                        if (isset($old['status']) && in_array($old['status'], ['approved', 'pending'])) {
                            $label  = 'Dévalidation du document';
                            $detail = $document->unapprove_reason ?? null;
                            $type   = 'warning';
                        }
                        break;
                    case 'paid':
                        $label  = 'Marqué comme payé';
                        $detail = isset($new['payment_method']) ? 'Mode : ' . $new['payment_method'] : null;
                        $type   = 'success';
                        break;
                    case 'cancelled':
                        $label  = 'Document annulé';
                        $type   = 'error';
                        break;
                    case 'pending':
                        $label  = 'Soumis pour validation';
                        $type   = 'info';
                        break;
                }
            } elseif ($audit->event === 'created') {
                $type = 'info';
            } elseif ($audit->event === 'updated') {
                $changed = array_keys($new);
                $ignored = ['updated_at', 'paid_amount', 'balance', 'subtotal', 'tax_amount', 'total_amount'];
                $meaningful = array_diff($changed, $ignored);
                if (empty($meaningful)) return null;
                $detail = 'Champs modifiés : ' . implode(', ', $meaningful);
            }

            return [
                'id'         => $audit->id,
                'event'      => $audit->event,
                'type'       => $type,
                'label'      => $label,
                'detail'     => $detail,
                'user'       => $audit->user ? ['id' => $audit->user->id, 'name' => $audit->user->full_name] : null,
                'created_at' => $audit->created_at->toISOString(),
            ];
        })->filter()->values()->toArray();
    }

    public function markAsPaid(string $id, array $data): BillingDocument
    {
        $document = $this->repository->findByIdOrFail($id);

        if (!in_array($document->type, ['FA', 'LLD'], true)) {
            abort(422, 'Seules les factures peuvent être marquées comme payées.');
        }

        $document->update([
            'status'            => 'paid',
            'paid_amount'       => $document->total_amount,
            'balance'           => 0,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'paid_at'           => now(),
        ]);
        $document = $document->fresh();

        $this->notificationService->notifyBillingPaid($document);

        return $document;
    }

    /**
     * Inverse of markAsPaid() — used when the reservation payment that
     * settled this invoice gets deleted, so the invoice doesn't stay
     * permanently stuck at "paid" with no way to correct it.
     */
    public function revertPayment(string $id): BillingDocument
    {
        $document = $this->repository->findByIdOrFail($id);

        $document->update([
            'status'            => 'approved',
            'paid_amount'       => 0,
            'balance'           => $document->total_amount,
            'payment_method'    => null,
            'payment_reference' => null,
            'paid_at'           => null,
        ]);

        return $document->fresh();
    }

    public function createFromReservation(string $reservationId, string $type = 'FA'): BillingDocument
    {
        $reservation = \App\Models\Reservation::with(['client', 'vehicle', 'agency'])->findOrFail($reservationId);

        return $this->create([
            'type'          => $type,
            'agency_id'     => $reservation->agency_id,
            'reservation_id'=> $reservation->id,
            'client_id'     => $reservation->client_id,
            'client_name'   => $reservation->client->full_name,
            'client_email'  => $reservation->client->email,
            'client_phone'  => $reservation->client->phone,
            'client_address'=> $reservation->client->address,
            'issue_date'    => now(),
            'due_date'      => now()->addDays(30),
            'items'         => [
                [
                    'description' => "Location {$reservation->vehicle->full_name} ({$reservation->total_days} jours)",
                    'quantity'    => $reservation->total_days,
                    'unit_price'  => $reservation->daily_rate,
                ],
            ],
        ]);
    }

    public function statistics(array $filters = []): array
    {
        $query = BillingDocument::query();
        if (!empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return [
            'total_documents' => $query->count(),
            'total_amount'    => $query->sum('total_amount'),
            'paid_amount'     => $query->sum('paid_amount'),
            'balance'         => $query->sum('balance'),
            'by_type'         => BillingDocument::selectRaw('type, COUNT(*) as count, SUM(total_amount) as total')
                ->groupBy('type')->get(),
            'by_status'       => BillingDocument::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')->get(),
        ];
    }
}
