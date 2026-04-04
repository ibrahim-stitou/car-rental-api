<?php

namespace App\Modules\Insurance\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Insurance\Requests\StoreInsuranceRequest;
use App\Modules\Insurance\Requests\UpdateInsuranceRequest;
use App\Modules\Insurance\Resources\InsuranceResource;
use App\Modules\Insurance\Services\InsuranceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsuranceController extends BaseController
{
    public function __construct(protected InsuranceService $service) {}

    /**
     * @OA\Get(path="/insurances", summary="Liste des assurances", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="vehicle_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"third_party","comprehensive","all_risk"})),
     *   @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['vehicle_id', 'type', 'is_active']);
        $data = $this->service->list($filters, $request->integer('per_page', 15));
        return $this->paginated($data, InsuranceResource::class);
    }

    /**
     * @OA\Post(path="/insurances", summary="Créer une assurance", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"vehicle_id","insurance_company","policy_number","type","start_date","end_date","premium_amount"},
     *     @OA\Property(property="vehicle_id", type="string", format="uuid"),
     *     @OA\Property(property="insurance_company", type="string"),
     *     @OA\Property(property="policy_number", type="string"),
     *     @OA\Property(property="type", type="string", enum={"third_party","comprehensive","all_risk"}),
     *     @OA\Property(property="start_date", type="string", format="date"),
     *     @OA\Property(property="end_date", type="string", format="date"),
     *     @OA\Property(property="premium_amount", type="number"),
     *     @OA\Property(property="deductible_amount", type="number"),
     *     @OA\Property(property="agent_name", type="string"),
     *     @OA\Property(property="agent_phone", type="string"),
     *     @OA\Property(property="notes", type="string")
     *   )),
     *   @OA\Response(response=201, description="Insurance created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreInsuranceRequest $request): JsonResponse
    {
        $insurance = $this->service->create($request->validated());
        return $this->created(new InsuranceResource($insurance), 'Insurance created');
    }

    /**
     * @OA\Get(path="/insurances/{id}", summary="Détails d'une assurance", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        return $this->success(new InsuranceResource($this->service->find($id)));
    }

    /**
     * @OA\Put(path="/insurances/{id}", summary="Modifier une assurance", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateInsuranceRequest $request, string $id): JsonResponse
    {
        $insurance = $this->service->update($id, $request->validated());
        return $this->success(new InsuranceResource($insurance), 'Insurance updated');
    }

    /**
     * @OA\Delete(path="/insurances/{id}", summary="Supprimer une assurance", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Insurance deleted');
    }

    /**
     * @OA\Get(path="/insurances/expired", summary="Assurances expirées", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function expired(Request $request): JsonResponse
    {
        return $this->paginated($this->service->expired($request->integer('per_page', 15)), InsuranceResource::class);
    }

    /**
     * @OA\Get(path="/insurances/expiring-soon", summary="Assurances expirant bientôt", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="days", in="query", @OA\Schema(type="integer", default=30)),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);
        return $this->paginated($this->service->expiringSoon($days, $request->integer('per_page', 15)), InsuranceResource::class);
    }

    /**
     * @OA\Post(path="/insurances/{id}/policy-document", summary="Upload police d'assurance", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="policy_document", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadPolicyDocument(Request $request, string $id): JsonResponse
    {
        $request->validate(['policy_document' => 'required|file|mimes:pdf|max:20480']);
        $insurance = $this->service->find($id);
        $insurance->uploadMedia($request->file('policy_document'), 'policy_document');
        return $this->success(['url' => $insurance->getFirstMediaUrl('policy_document')], 'Policy document uploaded');
    }

    /**
     * @OA\Post(path="/insurances/{id}/green-card", summary="Upload carte verte", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="green_card", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadGreenCard(Request $request, string $id): JsonResponse
    {
        $request->validate(['green_card' => 'required|file|mimes:jpeg,png,pdf|max:5120']);
        $insurance = $this->service->find($id);
        $insurance->uploadMedia($request->file('green_card'), 'green_card');
        return $this->success(['url' => $insurance->getFirstMediaUrl('green_card')], 'Green card uploaded');
    }

    /**
     * @OA\Post(path="/insurances/{id}/attachments", summary="Upload pièces jointes", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="attachments[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadAttachments(Request $request, string $id): JsonResponse
    {
        $request->validate(['attachments' => 'required|array', 'attachments.*' => 'file|max:10240']);
        $insurance = $this->service->find($id);
        $insurance->uploadMultipleMedia($request->file('attachments'), 'attachments');
        return $this->success($insurance->getMediaByCollection('attachments'), 'Attachments uploaded');
    }

    /**
     * @OA\Delete(path="/insurances/{id}/media/{mediaId}", summary="Supprimer un média", tags={"Insurances"}, security={{"bearerAuth":{}}},
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

    /**
     * @OA\Get(path="/insurances/{id}/media", summary="Lister les médias d'une assurance", tags={"Insurances"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function getMedia(string $id): JsonResponse
    {
        return $this->success($this->service->find($id)->getAllMediaFormatted());
    }
}
