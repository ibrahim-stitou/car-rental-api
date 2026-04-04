<?php

namespace App\Modules\Client\Services;

use App\Models\Client;
use App\Modules\Client\Repositories\ClientRepository;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientService
{
    public function __construct(
        protected ClientRepository $repository,
        protected NotificationService $notificationService,
    ) {}

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['agency'], function ($dataTable) {
            $dataTable->addColumn('full_name', fn($c) => $c->full_name)
                ->addColumn('agency_name', fn($c) => $c->agency?->name ?? '—')
                ->addColumn('is_license_valid', fn($c) => $c->is_license_valid ? 'Oui' : 'Non')
                ->addColumn('blacklisted', fn($c) => $c->is_blacklisted ? 'Oui' : 'Non');
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['agency', 'creator']);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($term, $this->repository->getSearchFields(), ['agency'], $perPage);
    }

    public function find(string $id): Client
    {
        return $this->repository->findByIdOrFail($id, ['agency', 'creator', 'reservations']);
    }

    public function create(array $data): Client
    {
        $data['created_by'] = auth('api')->id();
        return $this->repository->create($data);
    }

    public function update(string $id, array $data): Client
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function restore(string $id): Client
    {
        return $this->repository->restore($id);
    }

    public function blacklist(string $id, string $reason): Client
    {
        $client = $this->repository->findByIdOrFail($id);
        $client->update(['is_blacklisted' => true, 'blacklist_reason' => $reason]);
        $client = $client->fresh();

        $this->notificationService->notifyClientBlacklisted($client);

        return $client;
    }

    public function unblacklist(string $id): Client
    {
        $client = $this->repository->findByIdOrFail($id);
        $client->update(['is_blacklisted' => false, 'blacklist_reason' => null]);
        return $client->fresh();
    }

    public function blacklisted(int $perPage = 15): LengthAwarePaginator
    {
        return Client::blacklisted()->with('agency')->paginate($perPage);
    }
}
