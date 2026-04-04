<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(
        protected UserRepository $repository,
        protected NotificationService $notificationService,
    ) {}

    public function datatable(array $filters = [])
    {
        return $this->repository->datatable($filters, ['agency', 'roles'], function ($dataTable) {
            $dataTable->addColumn('full_name', fn($u) => $u->full_name)
                ->addColumn('agency_name', fn($u) => $u->agency?->name ?? '—')
                ->addColumn('roles_list', fn($u) => $u->roles->pluck('name')->join(', '))
                ->addColumn('active_status', fn($u) => $u->is_active ? 'Actif' : 'Inactif');
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['agency', 'roles']);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($term, $this->repository->getSearchFields(), ['agency', 'roles'], $perPage);
    }

    public function find(string $id): User
    {
        return $this->repository->findByIdOrFail($id, ['agency', 'roles', 'permissions']);
    }

    public function create(array $data): User
    {
        $user = $this->repository->create($data);
        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }
        $user = $user->fresh(['roles']);

        $this->notificationService->notifyUserCreated($user);

        return $user;
    }

    public function update(string $id, array $data): User
    {
        return $this->repository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function restore(string $id): User
    {
        return $this->repository->restore($id);
    }

    public function toggleActive(string $id): User
    {
        $user = $this->repository->findByIdOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
        $user = $user->fresh();

        if (!$user->is_active) {
            $this->notificationService->notifyUserDeactivated($user);
        }

        return $user;
    }

    public function assignRole(string $id, string $role): User
    {
        $user = $this->repository->findByIdOrFail($id);
        $user->assignRole($role);
        return $user->fresh(['roles']);
    }

    public function removeRole(string $id, string $role): User
    {
        $user = $this->repository->findByIdOrFail($id);
        $user->removeRole($role);
        return $user->fresh(['roles']);
    }
}
