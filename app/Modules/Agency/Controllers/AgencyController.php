<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Agency\Requests\StoreAgencyRequest;
use App\Modules\Agency\Requests\UpdateAgencyRequest;
use App\Modules\Agency\Resources\AgencyResource;
use App\Modules\Agency\Services\AgencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends BaseController
{
    public function __construct(protected AgencyService $service)
    {
    }

    /**
     * @OA\Get(
     *   path="/agencies",
     *   summary="Liste des agences",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="city", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"name","created_at"})),
     *   @OA\Parameter(name="sort_dir", in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled('search')) {
            $data = $this->service->search($request->search, $request->integer('per_page', 15));
            return $this->paginated($data, AgencyResource::class);
        }

        $filters = $request->only(['city', 'is_active', 'name']);
        $data = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->input('sort_by', 'created_at'),
            $request->input('sort_dir', 'desc')
        );

        return $this->paginated($data, AgencyResource::class);
    }

    /**
     * @OA\Post(
     *   path="/agencies",
     *   summary="Créer une agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"name","email"},
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="email", type="string", format="email"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="city", type="string"),
     *     @OA\Property(property="country", type="string"),
     *     @OA\Property(property="phone", type="string"),
     *     @OA\Property(property="manager_id", type="string", format="uuid")
     *   )),
     *   @OA\Response(response=201, description="Agency created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreAgencyRequest $request): JsonResponse
    {
        $agency = $this->service->create($request->validated());
        return $this->created(new AgencyResource($agency), 'Agency created successfully');
    }

    /**
     * @OA\Get(
     *   path="/agencies/{id}",
     *   summary="Détails d'une agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $agency = $this->service->find($id);
        return $this->success(new AgencyResource($agency));
    }

    /**
     * @OA\Put(
     *   path="/agencies/{id}",
     *   summary="Modifier une agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="email", type="string", format="email"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="city", type="string"),
     *     @OA\Property(property="phone", type="string")
     *   )),
     *   @OA\Response(response=200, description="Agency updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateAgencyRequest $request, string $id): JsonResponse
    {
        $agency = $this->service->update($id, $request->validated());
        return $this->success(new AgencyResource($agency), 'Agency updated successfully');
    }

    /**
     * @OA\Delete(
     *   path="/agencies/{id}",
     *   summary="Supprimer une agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\Response(response=200, description="Agency deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Agency deleted successfully');
    }

    /**
     * @OA\Post(
     *   path="/agencies/{id}/logo",
     *   summary="Upload logo de l'agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="logo", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Logo uploaded")
     * )
     */
    public function uploadLogo(Request $request, string $id): JsonResponse
    {
        $request->validate(['logo' => 'required|image|mimes:jpeg,png,webp|max:5120']);
        $agency = $this->service->find($id);
        $agency->uploadMedia($request->file('logo'), 'logo');
        return $this->success(['logo' => $agency->getFirstMediaUrl('logo')], 'Logo uploaded successfully');
    }

    /**
     * @OA\Post(
     *   path="/agencies/{id}/documents",
     *   summary="Upload documents de l'agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="documents[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Documents uploaded")
     * )
     */
    public function uploadDocuments(Request $request, string $id): JsonResponse
    {
        $request->validate(['documents' => 'required|array', 'documents.*' => 'file|max:10240']);
        $agency = $this->service->find($id);
        $agency->uploadMultipleMedia($request->file('documents'), 'documents');
        return $this->success($agency->getMediaByCollection('documents'), 'Documents uploaded successfully');
    }

    public function deleteMedia(string $id, int $mediaId): JsonResponse
    {
        $agency = $this->service->find($id);
        $media = $agency->media()->findOrFail($mediaId);
        $media->delete();
        return $this->success(null, 'Media deleted successfully');
    }

    public function vehicles(string $id): JsonResponse
    {
        $agency = $this->service->find($id);
        return $this->success($agency->vehicles);
    }

    /**
     * @OA\Post(
     *   path="/agencies/{id}/restore",
     *   summary="Restaurer une agence supprimée",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Agency restored")
     * )
     */
    public function restore(string $id): JsonResponse
    {
        $agency = $this->service->restore($id);
        return $this->success(new AgencyResource($agency), 'Agency restored successfully');
    }
}

