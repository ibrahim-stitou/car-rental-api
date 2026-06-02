<?php

namespace App\Modules\Claim\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Claim\Requests\StoreClaimRequest;
use App\Modules\Claim\Requests\UpdateClaimRequest;
use App\Modules\Claim\Resources\ClaimResource;
use App\Modules\Claim\Services\ClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends BaseController
{
    public function __construct(protected ClaimService $service) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->filled('search')) {
            $data = $this->service->search($request->search, $request->integer('per_page', 15));
            return $this->paginated($data, ClaimResource::class);
        }

        $filters = $request->only(['vehicle_id', 'client_id', 'status', 'accident_type', 'is_client_responsible']);
        $data = $this->service->list($filters, $request->integer('per_page', 15));
        return $this->paginated($data, ClaimResource::class);
    }

    public function store(StoreClaimRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), ['created_by' => auth()->id()]);
        $claim = $this->service->create($data);
        return $this->created(new ClaimResource($claim->load(['vehicle', 'client', 'reservation', 'creator'])), 'Sinistre créé');
    }

    public function show(string $id): JsonResponse
    {
        return $this->success(new ClaimResource($this->service->find($id)));
    }

    public function update(UpdateClaimRequest $request, string $id): JsonResponse
    {
        $claim = $this->service->update($id, $request->validated());
        return $this->success(new ClaimResource($claim->load(['vehicle', 'client', 'reservation', 'creator'])), 'Sinistre mis à jour');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Sinistre supprimé');
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:open,under_review,insurance_claimed,settled,closed,rejected']);
        $claim = $this->service->updateStatus($id, $request->status);
        return $this->success(new ClaimResource($claim), 'Statut mis à jour');
    }

    public function statistics(): JsonResponse
    {
        return $this->success($this->service->statistics());
    }

    // Media endpoints
    public function uploadPhotos(Request $request, string $id): JsonResponse
    {
        $request->validate(['photos' => 'required|array|max:10', 'photos.*' => 'image|mimes:jpeg,png,webp|max:5120']);
        $claim = $this->service->find($id);
        $claim->uploadMultipleMedia($request->file('photos'), 'photos');
        return $this->success($claim->getMediaByCollection('photos'), 'Photos téléversées');
    }

    public function uploadDocuments(Request $request, string $id): JsonResponse
    {
        $request->validate(['documents' => 'required|array', 'documents.*' => 'file|max:10240']);
        $claim = $this->service->find($id);
        $claim->uploadMultipleMedia($request->file('documents'), 'documents');
        return $this->success($claim->getMediaByCollection('documents'), 'Documents téléversés');
    }

    public function uploadInsuranceDocuments(Request $request, string $id): JsonResponse
    {
        $request->validate(['documents' => 'required|array', 'documents.*' => 'file|max:10240']);
        $claim = $this->service->find($id);
        $claim->uploadMultipleMedia($request->file('documents'), 'insurance_documents');
        return $this->success($claim->getMediaByCollection('insurance_documents'), 'Documents assurance téléversés');
    }

    public function deleteMedia(string $id, int $mediaId): JsonResponse
    {
        $claim = $this->service->find($id);
        $claim->media()->findOrFail($mediaId)->delete();
        return $this->success(null, 'Média supprimé');
    }
}
