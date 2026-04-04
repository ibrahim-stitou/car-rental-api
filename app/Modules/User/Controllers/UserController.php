<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\User\Requests\StoreUserRequest;
use App\Modules\User\Requests\UpdateUserRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct(protected UserService $service) {}

    /**
     * @OA\Get(path="/users", summary="Liste des utilisateurs", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled('search')) {
            $data = $this->service->search($request->search, $request->integer('per_page', 15));
            return $this->paginated($data, UserResource::class);
        }

        $filters = $request->only(['agency_id', 'is_active']);
        $data = $this->service->list($filters, $request->integer('per_page', 15));
        return $this->paginated($data, UserResource::class);
    }

    /**
     * @OA\Post(path="/users", summary="Créer un utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"first_name","last_name","email","password","password_confirmation"},
     *     @OA\Property(property="first_name", type="string"),
     *     @OA\Property(property="last_name", type="string"),
     *     @OA\Property(property="email", type="string", format="email"),
     *     @OA\Property(property="password", type="string"),
     *     @OA\Property(property="password_confirmation", type="string"),
     *     @OA\Property(property="phone", type="string"),
     *     @OA\Property(property="agency_id", type="string", format="uuid"),
     *     @OA\Property(property="role", type="string")
     *   )),
     *   @OA\Response(response=201, description="User created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated());
        return $this->created(new UserResource($user), 'User created');
    }

    /**
     * @OA\Get(path="/users/{id}", summary="Détails d'un utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        return $this->success(new UserResource($this->service->find($id)));
    }

    /**
     * @OA\Put(path="/users/{id}", summary="Modifier un utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = $this->service->update($id, $request->validated());
        return $this->success(new UserResource($user), 'User updated');
    }

    /**
     * @OA\Delete(path="/users/{id}", summary="Supprimer un utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'User deleted');
    }

    /**
     * @OA\Patch(path="/users/{id}/toggle-active", summary="Activer/Désactiver un utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Status toggled")
     * )
     */
    public function toggleActive(string $id): JsonResponse
    {
        $user = $this->service->toggleActive($id);
        return $this->success(new UserResource($user), 'User status toggled');
    }

    /**
     * @OA\Post(path="/users/{id}/avatar", summary="Upload avatar utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="avatar", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Avatar uploaded")
     * )
     */
    public function uploadAvatar(Request $request, string $id): JsonResponse
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,webp|max:2048']);
        $user = $this->service->find($id);
        $user->addMedia($request->file('avatar'))->toMediaCollection('avatar');
        return $this->success([
            'url'   => $user->getFirstMediaUrl('avatar'),
            'thumb' => $user->getFirstMediaUrl('avatar', 'thumb'),
        ], 'Avatar uploaded');
    }

    /**
     * @OA\Post(path="/users/{id}/assign-role", summary="Assigner un rôle", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"role"},
     *     @OA\Property(property="role", type="string")
     *   )),
     *   @OA\Response(response=200, description="Role assigned")
     * )
     */
    public function assignRole(Request $request, string $id): JsonResponse
    {
        $request->validate(['role' => 'required|string|exists:roles,name']);
        $user = $this->service->assignRole($id, $request->role);
        return $this->success(new UserResource($user), 'Role assigned');
    }

    /**
     * @OA\Delete(path="/users/{id}/remove-role", summary="Retirer un rôle", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"role"},
     *     @OA\Property(property="role", type="string")
     *   )),
     *   @OA\Response(response=200, description="Role removed")
     * )
     */
    public function removeRole(Request $request, string $id): JsonResponse
    {
        $request->validate(['role' => 'required|string|exists:roles,name']);
        $user = $this->service->removeRole($id, $request->role);
        return $this->success(new UserResource($user), 'Role removed');
    }

    /**
     * @OA\Get(path="/users/{id}/permissions", summary="Permissions d'un utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function permissions(string $id): JsonResponse
    {
        $user = $this->service->find($id);
        return $this->success([
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * @OA\Get(path="/users/{id}/activity-logs", summary="Logs d'activité d'un utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function activityLogs(string $id): JsonResponse
    {
        $user = $this->service->find($id);
        $audits = $user->audits()->latest()->paginate(15);
        return $this->success($audits);
    }

    /**
     * @OA\Post(path="/users/{id}/restore", summary="Restaurer un utilisateur", tags={"Users"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Restored")
     * )
     */
    public function restore(string $id): JsonResponse
    {
        $user = $this->service->restore($id);
        return $this->success(new UserResource($user), 'User restored');
    }
}
