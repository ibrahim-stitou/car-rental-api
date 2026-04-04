<?php

namespace App\Modules\Role\Controllers;

use App\Core\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends BaseController
{
    /**
     * @OA\Get(path="/permissions", summary="Liste des permissions", tags={"Roles"}, security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::all();
        return $this->success($permissions);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|unique:permissions,name',
            'guard_name' => 'nullable|string',
        ]);

        $permission = Permission::create([
            'name'       => $request->name,
            'guard_name' => $request->guard_name ?? 'api',
        ]);

        return $this->created($permission, 'Permission created');
    }

    public function destroy(string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        return $this->success(null, 'Permission deleted');
    }
}

