<?php

namespace App\Modules\Vignette\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Vignette\Requests\StoreVignetteRequest;
use App\Modules\Vignette\Requests\UpdateVignetteRequest;
use App\Modules\Vignette\Resources\VignetteResource;
use App\Modules\Vignette\Services\VignetteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VignetteController extends BaseController
{
    public function __construct(protected VignetteService $service) {}

    /**
     * @OA\Get(path="/vignettes", summary="Liste des vignettes", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="vehicle_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="is_paid", in="query", @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="year", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['vehicle_id', 'is_paid', 'year']);
        $data = $this->service->list($filters, $request->integer('per_page', 15));
        return $this->paginated($data, VignetteResource::class);
    }

    /**
     * @OA\Post(path="/vignettes", summary="Créer une vignette", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"vehicle_id","year","issue_date","expiry_date","amount"},
     *     @OA\Property(property="vehicle_id", type="string", format="uuid"),
     *     @OA\Property(property="year", type="integer"),
     *     @OA\Property(property="issue_date", type="string", format="date"),
     *     @OA\Property(property="expiry_date", type="string", format="date"),
     *     @OA\Property(property="amount", type="number"),
     *     @OA\Property(property="payment_method", type="string"),
     *     @OA\Property(property="payment_reference", type="string")
     *   )),
     *   @OA\Response(response=201, description="Vignette created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreVignetteRequest $request): JsonResponse
    {
        $vignette = $this->service->create($request->validated());
        return $this->created(new VignetteResource($vignette), 'Vignette created');
    }

    /**
     * @OA\Get(path="/vignettes/{id}", summary="Détails d'une vignette", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        return $this->success(new VignetteResource($this->service->find($id)));
    }

    /**
     * @OA\Put(path="/vignettes/{id}", summary="Modifier une vignette", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateVignetteRequest $request, string $id): JsonResponse
    {
        $vignette = $this->service->update($id, $request->validated());
        return $this->success(new VignetteResource($vignette), 'Vignette updated');
    }

    /**
     * @OA\Delete(path="/vignettes/{id}", summary="Supprimer une vignette", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Vignette deleted');
    }

    /**
     * @OA\Get(path="/vignettes/expired", summary="Vignettes expirées", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function expired(Request $request): JsonResponse
    {
        return $this->paginated($this->service->expired($request->integer('per_page', 15)), VignetteResource::class);
    }

    /**
     * @OA\Get(path="/vignettes/unpaid", summary="Vignettes non payées", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function unpaid(Request $request): JsonResponse
    {
        return $this->paginated($this->service->unpaid($request->integer('per_page', 15)), VignetteResource::class);
    }

    /**
     * @OA\Patch(path="/vignettes/{id}/mark-paid", summary="Marquer vignette comme payée", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(
     *     @OA\Property(property="payment_method", type="string", enum={"cash","bank_transfer","online"}),
     *     @OA\Property(property="payment_reference", type="string")
     *   )),
     *   @OA\Response(response=200, description="Marked as paid")
     * )
     */
    public function markAsPaid(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'payment_method'    => 'nullable|in:cash,bank_transfer,online',
            'payment_reference' => 'nullable|string|max:255',
        ]);
        $vignette = $this->service->markAsPaid($id, $request->all());
        return $this->success(new VignetteResource($vignette), 'Vignette marked as paid');
    }

    /**
     * @OA\Post(path="/vignettes/{id}/document", summary="Upload document vignette", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="document", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadDocument(Request $request, string $id): JsonResponse
    {
        $request->validate(['document' => 'required|file|mimes:jpeg,png,pdf|max:5120']);
        $vignette = $this->service->find($id);
        $vignette->uploadMedia($request->file('document'), 'vignette_document');
        return $this->success(['url' => $vignette->getFirstMediaUrl('vignette_document')], 'Document uploaded');
    }

    /**
     * @OA\Post(path="/vignettes/{id}/payment-proof", summary="Upload preuve de paiement", tags={"Vignettes"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="payment_proof", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadPaymentProof(Request $request, string $id): JsonResponse
    {
        $request->validate(['payment_proof' => 'required|file|mimes:jpeg,png,pdf|max:5120']);
        $vignette = $this->service->find($id);
        $vignette->uploadMedia($request->file('payment_proof'), 'payment_proof');
        return $this->success(['url' => $vignette->getFirstMediaUrl('payment_proof')], 'Payment proof uploaded');
    }

    /**
     * @OA\Delete(path="/vignettes/{id}/media/{mediaId}", summary="Supprimer un média", tags={"Vignettes"}, security={{"bearerAuth":{}}},
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

