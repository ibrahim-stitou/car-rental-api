<?php

namespace App\Modules\Vehicle\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Vehicle\Requests\StoreVehicleRequest;
use App\Modules\Vehicle\Requests\UpdateVehicleRequest;
use App\Modules\Vehicle\Resources\VehicleResource;
use App\Modules\Vehicle\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends BaseController
{
    public function __construct(protected VehicleService $service)
    {
    }

    /**
     * @OA\Get(
     *   path="/vehicles",
     *   summary="Liste des véhicules",
     *   tags={"Vehicles"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"available","rented","maintenance","out_of_service"})),
     *   @OA\Parameter(name="category", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled('search')) {
            $data = $this->service->search($request->search, $request->integer('per_page', 15));
            return $this->paginated($data, VehicleResource::class);
        }

        $filters = $request->only(['agency_id', 'status', 'category', 'fuel_type', 'transmission', 'is_active']);
        $data = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->input('sort_by', 'created_at'),
            $request->input('sort_dir', 'desc')
        );

        return $this->paginated($data, VehicleResource::class);
    }

    /**
     * @OA\Post(
     *   path="/vehicles",
     *   summary="Créer un véhicule",
     *   tags={"Vehicles"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"agency_id","brand","model","year","registration_number","vin","category","fuel_type","transmission","seats","daily_rate","deposit_amount","mileage"},
     *     @OA\Property(property="agency_id", type="string", format="uuid"),
     *     @OA\Property(property="brand", type="string"),
     *     @OA\Property(property="model", type="string"),
     *     @OA\Property(property="year", type="integer"),
     *     @OA\Property(property="registration_number", type="string"),
     *     @OA\Property(property="vin", type="string"),
     *     @OA\Property(property="category", type="string"),
     *     @OA\Property(property="fuel_type", type="string"),
     *     @OA\Property(property="transmission", type="string"),
     *     @OA\Property(property="seats", type="integer"),
     *     @OA\Property(property="daily_rate", type="number"),
     *     @OA\Property(property="deposit_amount", type="number"),
     *     @OA\Property(property="mileage", type="integer")
     *   )),
     *   @OA\Response(response=201, description="Vehicle created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->service->create($request->validated());
        return $this->created(new VehicleResource($vehicle), 'Vehicle created successfully');
    }

    /**
     * @OA\Get(
     *   path="/vehicles/{id}",
     *   summary="Détails d'un véhicule",
     *   tags={"Vehicles"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $vehicle = $this->service->find($id);
        return $this->success(new VehicleResource($vehicle));
    }

    /**
     * @OA\Put(
     *   path="/vehicles/{id}",
     *   summary="Modifier un véhicule",
     *   tags={"Vehicles"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Vehicle updated")
     * )
     */
    public function update(UpdateVehicleRequest $request, string $id): JsonResponse
    {
        $vehicle = $this->service->update($id, $request->validated());
        return $this->success(new VehicleResource($vehicle), 'Vehicle updated successfully');
    }

    /**
     * @OA\Delete(
     *   path="/vehicles/{id}",
     *   summary="Supprimer un véhicule",
     *   tags={"Vehicles"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Vehicle deleted")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Vehicle deleted successfully');
    }

    public function uploadPhotos(Request $request, string $id): JsonResponse
    {
        $request->validate(['photos' => 'required|array|max:10', 'photos.*' => 'image|mimes:jpeg,png,webp|max:5120']);
        $vehicle = $this->service->find($id);
        $vehicle->uploadMultipleMedia($request->file('photos'), 'photos');
        return $this->success($vehicle->getMediaByCollection('photos'), 'Photos uploaded successfully');
    }

    public function uploadRegistrationCard(Request $request, string $id): JsonResponse
    {
        $request->validate(['registration_card' => 'required|file|mimes:jpeg,png,pdf|max:5120']);
        $vehicle = $this->service->find($id);
        $vehicle->uploadMedia($request->file('registration_card'), 'registration_card');
        return $this->success(['url' => $vehicle->getFirstMediaUrl('registration_card')], 'Registration card uploaded');
    }

    public function uploadDocuments(Request $request, string $id): JsonResponse
    {
        $request->validate(['documents' => 'required|array', 'documents.*' => 'file|max:10240']);
        $vehicle = $this->service->find($id);
        $vehicle->uploadMultipleMedia($request->file('documents'), 'documents');
        return $this->success($vehicle->getMediaByCollection('documents'), 'Documents uploaded successfully');
    }

    public function deleteMedia(string $id, int $mediaId): JsonResponse
    {
        $vehicle = $this->service->find($id);
        $media = $vehicle->media()->findOrFail($mediaId);
        $media->delete();
        return $this->success(null, 'Media deleted successfully');
    }

    public function getMedia(string $id): JsonResponse
    {
        $vehicle = $this->service->find($id);
        return $this->success($vehicle->getAllMediaFormatted());
    }

    /**
     * @OA\Patch(
     *   path="/vehicles/{id}/status",
     *   summary="Modifier le statut d'un véhicule",
     *   tags={"Vehicles"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     @OA\Property(property="status", type="string", enum={"available","rented","maintenance","out_of_service"})
     *   )),
     *   @OA\Response(response=200, description="Status updated")
     * )
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:available,rented,maintenance,out_of_service']);
        $vehicle = $this->service->updateStatus($id, $request->status);
        return $this->success(new VehicleResource($vehicle), 'Vehicle status updated');
    }

    public function history(string $id): JsonResponse
    {
        $vehicle = $this->service->find($id);
        $audits = $vehicle->audits()->with('user')->latest()->paginate(15);
        return $this->success($audits);
    }

    public function reservations(string $id): JsonResponse
    {
        $vehicle = $this->service->find($id);
        return $this->success($vehicle->reservations()->with('client')->latest()->paginate(15));
    }

    public function restore(string $id): JsonResponse
    {
        $vehicle = $this->service->restore($id);
        return $this->success(new VehicleResource($vehicle), 'Vehicle restored successfully');
    }
}

