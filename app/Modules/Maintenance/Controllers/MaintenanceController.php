<?php

namespace App\Modules\Maintenance\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Maintenance\Requests\StoreMaintenanceRequest;
use App\Modules\Maintenance\Requests\UpdateMaintenanceRequest;
use App\Modules\Maintenance\Resources\MaintenanceResource;
use App\Modules\Maintenance\Services\MaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends BaseController
{
    public function __construct(protected MaintenanceService $service) {}

    /**
     * @OA\Get(path="/maintenances", summary="Liste des maintenances", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="vehicle_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"scheduled","in_progress","completed","cancelled"})),
     *   @OA\Parameter(name="priority", in="query", @OA\Schema(type="string", enum={"low","medium","high","urgent"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['vehicle_id', 'status', 'priority', 'type']);
        $data = $this->service->list($filters, $request->integer('per_page', 15));
        return $this->paginated($data, MaintenanceResource::class);
    }

    /**
     * @OA\Post(path="/maintenances", summary="Créer une maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"vehicle_id","type","description","maintenance_date"},
     *     @OA\Property(property="vehicle_id", type="string", format="uuid"),
     *     @OA\Property(property="type", type="string"),
     *     @OA\Property(property="description", type="string"),
     *     @OA\Property(property="maintenance_date", type="string", format="date"),
     *     @OA\Property(property="mileage_at_service", type="integer"),
     *     @OA\Property(property="next_service_mileage", type="integer"),
     *     @OA\Property(property="next_service_date", type="string", format="date"),
     *     @OA\Property(property="cost", type="number"),
     *     @OA\Property(property="service_provider", type="string"),
     *     @OA\Property(property="status", type="string", enum={"scheduled","in_progress","completed","cancelled"}),
     *     @OA\Property(property="priority", type="string", enum={"low","medium","high","urgent"})
     *   )),
     *   @OA\Response(response=201, description="Maintenance created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreMaintenanceRequest $request): JsonResponse
    {
        $maintenance = $this->service->create($request->validated());
        return $this->created(new MaintenanceResource($maintenance->load(['vehicle', 'creator'])), 'Maintenance created');
    }

    /**
     * @OA\Get(path="/maintenances/{id}", summary="Détails d'une maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        return $this->success(new MaintenanceResource($this->service->find($id)));
    }

    /**
     * @OA\Put(path="/maintenances/{id}", summary="Modifier une maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateMaintenanceRequest $request, string $id): JsonResponse
    {
        $maintenance = $this->service->update($id, $request->validated());
        return $this->success(new MaintenanceResource($maintenance), 'Maintenance updated');
    }

    /**
     * @OA\Delete(path="/maintenances/{id}", summary="Supprimer une maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Maintenance deleted');
    }

    /**
     * @OA\Patch(path="/maintenances/{id}/complete", summary="Terminer une maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(
     *     @OA\Property(property="cost", type="number")
     *   )),
     *   @OA\Response(response=200, description="Completed")
     * )
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['cost' => 'nullable|numeric|min:0']);
        $maintenance = $this->service->complete($id, $data);
        return $this->success(new MaintenanceResource($maintenance), 'Maintenance completed');
    }

    /**
     * @OA\Patch(path="/maintenances/{id}/cancel", summary="Annuler une maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Cancelled")
     * )
     */
    public function cancel(string $id): JsonResponse
    {
        $maintenance = $this->service->cancel($id);
        return $this->success(new MaintenanceResource($maintenance), 'Maintenance cancelled');
    }

    /**
     * @OA\Get(path="/maintenances/scheduled", summary="Maintenances planifiées", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function scheduled(Request $request): JsonResponse
    {
        return $this->paginated($this->service->scheduled($request->integer('per_page', 15)), MaintenanceResource::class);
    }

    /**
     * @OA\Get(path="/maintenances/overdue", summary="Maintenances en retard", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function overdue(Request $request): JsonResponse
    {
        return $this->paginated($this->service->overdue($request->integer('per_page', 15)), MaintenanceResource::class);
    }

    /**
     * @OA\Post(path="/maintenances/{id}/invoices", summary="Upload factures maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="invoices[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadInvoices(Request $request, string $id): JsonResponse
    {
        $request->validate(['invoices' => 'required|array', 'invoices.*' => 'file|mimes:pdf|max:10240']);
        $maintenance = $this->service->find($id);
        $maintenance->uploadMultipleMedia($request->file('invoices'), 'invoices');
        return $this->success($maintenance->getMediaByCollection('invoices'), 'Invoices uploaded');
    }

    /**
     * @OA\Post(path="/maintenances/{id}/photos-before", summary="Upload photos avant maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="photos[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadPhotosBefore(Request $request, string $id): JsonResponse
    {
        $request->validate(['photos' => 'required|array|max:10', 'photos.*' => 'image|mimes:jpeg,png,webp|max:5120']);
        $maintenance = $this->service->find($id);
        $maintenance->uploadMultipleMedia($request->file('photos'), 'photos_before');
        return $this->success($maintenance->getMediaByCollection('photos_before'), 'Photos before uploaded');
    }

    /**
     * @OA\Post(path="/maintenances/{id}/photos-after", summary="Upload photos après maintenance", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="photos[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadPhotosAfter(Request $request, string $id): JsonResponse
    {
        $request->validate(['photos' => 'required|array|max:10', 'photos.*' => 'image|mimes:jpeg,png,webp|max:5120']);
        $maintenance = $this->service->find($id);
        $maintenance->uploadMultipleMedia($request->file('photos'), 'photos_after');
        return $this->success($maintenance->getMediaByCollection('photos_after'), 'Photos after uploaded');
    }

    /**
     * @OA\Delete(path="/maintenances/{id}/media/{mediaId}", summary="Supprimer un média", tags={"Maintenances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="mediaId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteMedia(string $id, int $mediaId): JsonResponse
    {
        $this->service->find($id)->media()->findOrFail($mediaId)->delete();
        return $this->success(null, 'Media deleted');
    }
}

