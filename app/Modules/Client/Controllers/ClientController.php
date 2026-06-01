<?php

namespace App\Modules\Client\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Client\Requests\StoreClientRequest;
use App\Modules\Client\Requests\UpdateClientRequest;
use App\Modules\Client\Resources\ClientResource;
use App\Modules\Client\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends BaseController
{
    public function __construct(protected ClientService $service) {}

    /**
     * @OA\Get(path="/clients", summary="Liste des clients", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="is_blacklisted", in="query", @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled('search')) {
            $data = $this->service->search($request->search, $request->integer('per_page', 15));
            return $this->paginated($data, ClientResource::class);
        }

        $filters = $request->only(['agency_id', 'is_blacklisted', 'city']);
        $data = $this->service->list($filters, $request->integer('per_page', 15));
        return $this->paginated($data, ClientResource::class);
    }

    /**
     * @OA\Post(path="/clients", summary="Créer un client", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"agency_id","first_name","last_name","email","phone"},
     *     @OA\Property(property="agency_id", type="string", format="uuid"),
     *     @OA\Property(property="first_name", type="string"),
     *     @OA\Property(property="last_name", type="string"),
     *     @OA\Property(property="email", type="string", format="email"),
     *     @OA\Property(property="phone", type="string"),
     *     @OA\Property(property="date_of_birth", type="string", format="date"),
     *     @OA\Property(property="nationality", type="string"),
     *     @OA\Property(property="id_type", type="string"),
     *     @OA\Property(property="id_number", type="string"),
     *     @OA\Property(property="id_expiry_date", type="string", format="date"),
     *     @OA\Property(property="driving_license_number", type="string"),
     *     @OA\Property(property="driving_license_category", type="string"),
     *     @OA\Property(property="driving_license_expiry", type="string", format="date"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="city", type="string"),
     *     @OA\Property(property="country", type="string")
     *   )),
     *   @OA\Response(response=201, description="Client created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->service->create($request->validated());
        return $this->created(new ClientResource($client), 'Client created');
    }

    /**
     * @OA\Get(path="/clients/{id}", summary="Détails d'un client", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        return $this->success(new ClientResource($this->service->find($id)));
    }

    /**
     * @OA\Put(path="/clients/{id}", summary="Modifier un client", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        $client = $this->service->update($id, $request->validated());
        return $this->success(new ClientResource($client), 'Client updated');
    }

    /**
     * @OA\Delete(path="/clients/{id}", summary="Supprimer un client", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Client deleted');
    }

    /**
     * @OA\Get(path="/clients/blacklisted", summary="Clients blacklistés", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function blacklisted(Request $request): JsonResponse
    {
        return $this->paginated($this->service->blacklisted($request->integer('per_page', 15)), ClientResource::class);
    }

    /**
     * @OA\Patch(path="/clients/{id}/blacklist", summary="Blacklister un client", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"reason"},
     *     @OA\Property(property="reason", type="string")
     *   )),
     *   @OA\Response(response=200, description="Client blacklisted")
     * )
     */
    public function blacklist(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);
        $client = $this->service->blacklist($id, $request->reason);
        return $this->success(new ClientResource($client), 'Client blacklisted');
    }

    /**
     * @OA\Patch(path="/clients/{id}/unblacklist", summary="Retirer du blacklist", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Client unblacklisted")
     * )
     */
    public function unblacklist(string $id): JsonResponse
    {
        $client = $this->service->unblacklist($id);
        return $this->success(new ClientResource($client), 'Client removed from blacklist');
    }

    /**
     * @OA\Post(path="/clients/{id}/id-document", summary="Upload pièce d'identité", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="id_document", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadIdDocument(Request $request, string $id): JsonResponse
    {
        $request->validate(['id_document' => 'required|file|mimes:jpeg,png,pdf|max:5120']);
        $client = $this->service->find($id);
        $client->uploadMedia($request->file('id_document'), 'id_document');
        return $this->success(['url' => $client->getFirstMediaUrl('id_document')], 'ID document uploaded');
    }

    /**
     * @OA\Post(path="/clients/{id}/driving-license", summary="Upload permis de conduire", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="driving_license", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadDrivingLicense(Request $request, string $id): JsonResponse
    {
        $request->validate(['driving_license' => 'required|file|mimes:jpeg,png,pdf|max:5120']);
        $client = $this->service->find($id);
        $client->uploadMedia($request->file('driving_license'), 'driving_license');
        return $this->success(['url' => $client->getFirstMediaUrl('driving_license')], 'Driving license uploaded');
    }

    /**
     * @OA\Post(path="/clients/{id}/selfie", summary="Upload selfie", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="selfie", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadSelfie(Request $request, string $id): JsonResponse
    {
        $request->validate(['selfie' => 'required|image|mimes:jpeg,png|max:2048']);
        $client = $this->service->find($id);
        $client->uploadMedia($request->file('selfie'), 'selfie');
        return $this->success(['url' => $client->getFirstMediaUrl('selfie')], 'Selfie uploaded');
    }

    /**
     * @OA\Post(path="/clients/{id}/documents", summary="Upload documents divers", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="documents[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadDocuments(Request $request, string $id): JsonResponse
    {
        $request->validate(['documents' => 'required|array', 'documents.*' => 'file|max:10240']);
        $client = $this->service->find($id);
        $client->uploadMultipleMedia($request->file('documents'), 'other_documents');
        return $this->success($client->getMediaByCollection('other_documents'), 'Documents uploaded');
    }

    /**
     * @OA\Delete(path="/clients/{id}/media/{mediaId}", summary="Supprimer un média", tags={"Clients"}, security={{"bearerAuth":{}}},
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
     * @OA\Get(path="/clients/{id}/reservations", summary="Réservations du client", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function reservations(string $id): JsonResponse
    {
        $client = $this->service->find($id);
        return $this->success($client->reservations()->with('vehicle')->latest()->paginate(15));
    }

    /**
     * @OA\Post(path="/clients/{id}/restore", summary="Restaurer un client", tags={"Clients"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Restored")
     * )
     */
    public function restore(string $id): JsonResponse
    {
        $client = $this->service->restore($id);
        return $this->success(new ClientResource($client), 'Client restored');
    }

    public function statistics(string $id): JsonResponse
    {
        $client = $this->service->find($id);
        $rq = $client->reservations();

        $totalAmount = (float) (clone $rq)->whereNotIn('status', ['cancelled'])->sum('total_amount');
        $totalPaid   = (float) $client->reservations()
            ->join('reservation_payments', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->sum('reservation_payments.amount');
        $creditBalance = max(0.0, $totalAmount - $totalPaid);

        $creditReservations = (clone $rq)->whereIn('status', ['completed', 'active'])
            ->selectRaw('reservations.id, reservation_number, total_amount, COALESCE(SUM(rp.amount),0) as paid_amount, total_amount - COALESCE(SUM(rp.amount),0) as credit_amount')
            ->leftJoin('reservation_payments as rp', 'reservations.id', '=', 'rp.reservation_id')
            ->groupBy('reservations.id', 'reservation_number', 'total_amount')
            ->havingRaw('credit_amount > 0')
            ->get();

        $lastReservation = (clone $rq)->with('vehicle:id,brand,model,registration_number')->latest()->first();

        return $this->success([
            'client' => new ClientResource($client),
            'reservations' => [
                'total'     => (clone $rq)->count(),
                'completed' => (clone $rq)->where('status', 'completed')->count(),
                'active'    => (clone $rq)->whereIn('status', ['active', 'confirmed', 'pending'])->count(),
                'cancelled' => (clone $rq)->where('status', 'cancelled')->count(),
            ],
            'financials' => [
                'total_amount'    => $totalAmount,
                'total_paid'      => $totalPaid,
                'credit_balance'  => $creditBalance,
            ],
            'credit_reservations' => $creditReservations,
            'last_reservation'    => $lastReservation,
        ]);
    }
}

