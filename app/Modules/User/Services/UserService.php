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
        return $this->repository->datatable($filters, ['agencies', 'roles'], function ($dataTable) {
            $dataTable->addColumn('full_name', fn($u) => $u->full_name)
                ->addColumn('agency_name', fn($u) => $u->agencies->pluck('name')->join(', ') ?: '—')
                ->addColumn('roles_list', fn($u) => $u->roles->pluck('name')->join(', '))
                ->addColumn('active_status', fn($u) => $u->is_active ? 'Actif' : 'Inactif');
        });
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters, ['agencies', 'roles']);
    }

    public function search(string $term, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($term, $this->repository->getSearchFields(), ['agencies', 'roles'], $perPage);
    }

    public function find(string $id): User
    {
        return $this->repository->findByIdOrFail($id, ['agencies', 'roles', 'permissions']);
    }

    public function create(array $data): User
    {
        $agencyIds = $data['agency_ids'] ?? null;
        unset($data['agency_ids']);

        $user = $this->repository->create($data);
        if ($agencyIds !== null) {
            $user->agencies()->sync($agencyIds);
        }
        if (!empty($data['role'])) {
            $user->assignRole($data['role']);
        }
        $user = $user->fresh(['roles', 'agencies']);

        $this->notificationService->notifyUserCreated($user);

        return $user;
    }

    public function update(string $id, array $data): User
    {
        $agencyIds = $data['agency_ids'] ?? null;
        unset($data['agency_ids']);

        $user = $this->repository->update($id, $data);
        if ($agencyIds !== null) {
            $user->agencies()->sync($agencyIds);
            $user = $user->fresh(['agencies']);
        }
        return $user;
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

    public function activate(string $id): User
    {
        $user = $this->repository->findByIdOrFail($id);
        $user->update(['is_active' => true]);
        return $user->fresh();
    }

    public function suspend(string $id): User
    {
        $user = $this->repository->findByIdOrFail($id);
        $user->update(['is_active' => false]);
        $user = $user->fresh();
        $this->notificationService->notifyUserDeactivated($user);
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
