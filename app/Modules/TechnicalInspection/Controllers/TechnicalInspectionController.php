<?php

namespace App\Modules\TechnicalInspection\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\TechnicalInspection\Requests\StoreTechnicalInspectionRequest;
use App\Modules\TechnicalInspection\Requests\UpdateTechnicalInspectionRequest;
use App\Modules\TechnicalInspection\Resources\TechnicalInspectionResource;
use App\Modules\TechnicalInspection\Services\TechnicalInspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicalInspectionController extends BaseController
{
    public function __construct(protected TechnicalInspectionService $service)
    {
    }

    /**
     * @OA\Get(path="/technical-inspections", summary="Liste des visites techniques", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="vehicle_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="result", in="query", @OA\Schema(type="string", enum={"passed","failed","pending"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['vehicle_id', 'result']);
        $data = $this->service->list($filters, $request->integer('per_page', 15));
        return $this->paginated($data, TechnicalInspectionResource::class);
    }

    /**
     * @OA\Post(path="/technical-inspections", summary="Créer une visite technique", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"vehicle_id","inspection_date","expiry_date","result","inspection_center"},
     *     @OA\Property(property="vehicle_id", type="string", format="uuid"),
     *     @OA\Property(property="inspection_date", type="string", format="date"),
     *     @OA\Property(property="expiry_date", type="string", format="date"),
     *     @OA\Property(property="result", type="string", enum={"passed","failed","pending"}),
     *     @OA\Property(property="inspection_center", type="string"),
     *     @OA\Property(property="inspector_name", type="string"),
     *     @OA\Property(property="observations", type="string"),
     *     @OA\Property(property="cost", type="number"),
     *     @OA\Property(property="next_inspection_date", type="string", format="date")
     *   )),
     *   @OA\Response(response=201, description="Created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreTechnicalInspectionRequest $request): JsonResponse
    {
        $inspection = $this->service->create($request->validated());
        return $this->created(new TechnicalInspectionResource($inspection), 'Technical inspection created');
    }

    /**
     * @OA\Get(path="/technical-inspections/{id}", summary="Détails d'une visite technique", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $inspection = $this->service->find($id);
        return $this->success(new TechnicalInspectionResource($inspection));
    }

    /**
     * @OA\Put(path="/technical-inspections/{id}", summary="Modifier une visite technique", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateTechnicalInspectionRequest $request, string $id): JsonResponse
    {
        $inspection = $this->service->update($id, $request->validated());
        return $this->success(new TechnicalInspectionResource($inspection), 'Technical inspection updated');
    }

    /**
     * @OA\Delete(path="/technical-inspections/{id}", summary="Supprimer une visite technique", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Technical inspection deleted');
    }

    /**
     * @OA\Get(path="/technical-inspections/expired", summary="Visites techniques expirées", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function expired(Request $request): JsonResponse
    {
        $data = $this->service->expired($request->integer('per_page', 15));
        return $this->paginated($data, TechnicalInspectionResource::class);
    }

    /**
     * @OA\Get(path="/technical-inspections/expiring-soon", summary="Visites techniques expirant bientôt", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="days", in="query", @OA\Schema(type="integer", default=30)),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        $data = $this->service->expiringSoon($days, $request->integer('per_page', 15));
        return $this->paginated($data, TechnicalInspectionResource::class);
    }

    /**
     * @OA\Post(path="/technical-inspections/{id}/report", summary="Upload rapport de visite", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="report", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadReport(Request $request, string $id): JsonResponse
    {
        $request->validate(['report' => 'required|file|mimes:pdf|max:10240']);
        $inspection = $this->service->find($id);
        $inspection->uploadMedia($request->file('report'), 'inspection_report');
        return $this->success(['url' => $inspection->getFirstMediaUrl('inspection_report')], 'Report uploaded');
    }

    /**
     * @OA\Post(path="/technical-inspections/{id}/photos", summary="Upload photos de visite", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="photos[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadPhotos(Request $request, string $id): JsonResponse
    {
        $request->validate(['photos' => 'required|array|max:5', 'photos.*' => 'image|mimes:jpeg,png,webp|max:5120']);
        $inspection = $this->service->find($id);
        $inspection->uploadMultipleMedia($request->file('photos'), 'photos');
        return $this->success($inspection->getMediaByCollection('photos'), 'Photos uploaded');
    }

    /**
     * @OA\Delete(path="/technical-inspections/{id}/media/{mediaId}", summary="Supprimer un média", tags={"Technical Inspections"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="mediaId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteMedia(string $id, int $mediaId): JsonResponse
    {
        $inspection = $this->service->find($id);
        $inspection->media()->findOrFail($mediaId)->delete();
        return $this->success(null, 'Media deleted');
    }
}

