<?php

namespace App\Modules\Vignette\Services;

use App\Models\Vignette;
use App\Modules\Vignette\Repositories\VignetteRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class VignetteService
{
    public function __construct(protected VignetteRepository $repository) {}

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['vehicle'], function ($dataTable) {
            $dataTable->addColumn('vehicle_name', fn($v) => $v->vehicle?->full_name ?? '—')
                ->addColumn('paid_status', fn($v) => $v->is_paid ? 'Payé' : 'Impayé')
                ->addColumn('days_until_expiry', fn($v) => $v->expiry_date ? now()->diffInDays($v->expiry_date, false) : null);
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['vehicle', 'creator']);
    }

    public function find(string $id): Vignette
    {
        return $this->repository->findByIdOrFail($id, ['vehicle', 'creator']);
    }

    public function create(array $data): Vignette
    {
        $data['created_by'] = auth('api')->id();
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Vignette
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function expired(int $perPage = 15): LengthAwarePaginator
    {
        return Vignette::expired()->with('vehicle')->paginate($perPage);
    }

    public function unpaid(int $perPage = 15): LengthAwarePaginator
    {
        return Vignette::unpaid()->with('vehicle')->paginate($perPage);
    }

    public function markAsPaid(string $id, array $data): Vignette
    {
        $vignette = $this->repository->findByIdOrFail($id);
        $vignette->update([
            'is_paid'           => true,
            'paid_at'           => now(),
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);
        return $vignette->fresh();
    }
}
