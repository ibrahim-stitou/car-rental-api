<?php

namespace App\Modules\Role\Controllers;

use App\Core\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends BaseController
{
    /**
     * @OA\Get(path="/roles", summary="Liste des rôles", tags={"Roles"}, security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        return $this->success($roles);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'guard_name'  => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'name'       => $request->name,
            'guard_name' => $request->guard_name ?? 'api',
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->created($role->load('permissions'), 'Role created');
    }

    public function show(string $id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);
        return $this->success($role);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|unique:roles,name,' . $id,
        ]);

        $role = Role::findOrFail($id);
        $role->update($request->only('name'));
        return $this->success($role->load('permissions'), 'Role updated');
    }

    public function destroy(string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        if (in_array($role->name, ['super-admin', 'admin'])) {
            return $this->error('Cannot delete system roles', 403);
        }
        $role->delete();
        return $this->success(null, 'Role deleted');
    }

    public function assignPermissions(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findOrFail($id);
        $role->syncPermissions($request->permissions);
        return $this->success($role->load('permissions'), 'Permissions assigned');
    }

    public function revokePermissions(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::findOrFail($id);
        foreach ($request->permissions as $permission) {
            $role->revokePermissionTo($permission);
        }
        return $this->success($role->load('permissions'), 'Permissions revoked');
    }
}

