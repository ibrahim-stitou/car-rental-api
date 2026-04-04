<?php

namespace App\Modules\Billing\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Billing\Requests\StoreBillingDocumentRequest;
use App\Modules\Billing\Requests\UpdateBillingDocumentRequest;
use App\Modules\Billing\Resources\BillingDocumentResource;
use App\Modules\Billing\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends BaseController
{
    public function __construct(protected BillingService $service) {}

    /**
     * @OA\Get(path="/billing", summary="Liste des documents de facturation", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="type", in="query", @OA\Schema(type="string", enum={"BC","BR","BL","DV","FA","AV"})),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled('search')) {
            $data = $this->service->search($request->search, $request->integer('per_page', 15));
            return $this->paginated($data, BillingDocumentResource::class);
        }

        $filters = $request->only(['type', 'status', 'agency_id', 'reservation_id', 'client_id']);
        $data = $this->service->list($filters, $request->integer('per_page', 15));
        return $this->paginated($data, BillingDocumentResource::class);
    }

    /**
     * @OA\Get(path="/billing/datatable", summary="DataTable des documents", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function datatable(Request $request)
    {
        $filters = $request->only(['type', 'status', 'agency_id']);
        return $this->datatableResponse($this->service->datatable($filters)->make(true));
    }

    /**
     * @OA\Post(path="/billing", summary="Créer un document", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=201, description="Document créé"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreBillingDocumentRequest $request): JsonResponse
    {
        $document = $this->service->create($request->validated());
        return $this->created(new BillingDocumentResource($document->load(['agency', 'items'])), 'Document créé avec succès');
    }

    /**
     * @OA\Get(path="/billing/{id}", summary="Détails d'un document", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        return $this->success(new BillingDocumentResource($this->service->find($id)));
    }

    /**
     * @OA\Put(path="/billing/{id}", summary="Modifier un document", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateBillingDocumentRequest $request, string $id): JsonResponse
    {
        $document = $this->service->update($id, $request->validated());
        return $this->success(new BillingDocumentResource($document), 'Document modifié avec succès');
    }

    /**
     * @OA\Delete(path="/billing/{id}", summary="Supprimer un document", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Document supprimé avec succès');
    }

    /**
     * @OA\Post(path="/billing/{id}/approve", summary="Approuver un document", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Approved"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function approve(string $id): JsonResponse
    {
        $document = $this->service->approve($id);
        return $this->success(new BillingDocumentResource($document), 'Document approuvé');
    }

    /**
     * @OA\Post(path="/billing/{id}/mark-paid", summary="Marquer comme payé", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(
     *     @OA\Property(property="payment_method", type="string", enum={"cash","card","bank_transfer","check","online"}),
     *     @OA\Property(property="payment_reference", type="string")
     *   )),
     *   @OA\Response(response=200, description="Marked as paid"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function markAsPaid(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'payment_method'    => 'nullable|in:cash,card,bank_transfer,check,online',
            'payment_reference' => 'nullable|string',
        ]);

        $document = $this->service->markAsPaid($id, $request->all());
        return $this->success(new BillingDocumentResource($document), 'Document marqué comme payé');
    }

    /**
     * @OA\Post(path="/billing/from-reservation/{reservationId}", summary="Créer facture depuis réservation", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="reservationId", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(
     *     @OA\Property(property="type", type="string", enum={"BC","DV","FA"})
     *   )),
     *   @OA\Response(response=201, description="Document créé depuis réservation"),
     *   @OA\Response(response=404, description="Reservation not found")
     * )
     */
    public function createFromReservation(Request $request, string $reservationId): JsonResponse
    {
        $request->validate(['type' => 'nullable|in:BC,DV,FA']);
        $type = $request->type ?? 'FA';

        $document = $this->service->createFromReservation($reservationId, $type);
        return $this->created(new BillingDocumentResource($document->load(['items'])), 'Document créé depuis la réservation');
    }

    /**
     * @OA\Get(path="/billing/statistics", summary="Statistiques facturation", tags={"Billing"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="type", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $filters = $request->only(['agency_id', 'type']);
        return $this->success($this->service->statistics($filters));
    }

    public function uploadPdf(Request $request, string $id): JsonResponse
    {
        $request->validate(['pdf' => 'required|file|mimes:pdf|max:10240']);
        $document = $this->service->find($id);
        $document->uploadMedia($request->file('pdf'), 'document_pdf');
        return $this->success(['url' => $document->getFirstMediaUrl('document_pdf')], 'PDF uploadé');
    }

    public function uploadAttachments(Request $request, string $id): JsonResponse
    {
        $request->validate(['attachments' => 'required|array', 'attachments.*' => 'file|max:10240']);
        $document = $this->service->find($id);
        $document->uploadMultipleMedia($request->file('attachments'), 'attachments');
        return $this->success($document->getMediaByCollection('attachments'), 'Pièces jointes uploadées');
    }

    public function deleteMedia(string $id, int $mediaId): JsonResponse
    {
        $document = $this->service->find($id);
        $media = $document->media()->findOrFail($mediaId);
        $media->delete();
        return $this->success(null, 'Média supprimé');
    }

    public function restore(string $id): JsonResponse
    {
        $document = $this->service->restore($id);
        return $this->success(new BillingDocumentResource($document), 'Document restauré');
    }
}

