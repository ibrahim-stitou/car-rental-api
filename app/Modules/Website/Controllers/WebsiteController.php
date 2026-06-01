<?php

namespace App\Modules\Website\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Vehicle\Resources\VehicleResource;
use App\Modules\Website\Resources\WebReservationResource;
use App\Modules\Website\Services\WebsiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteController extends BaseController
{
    public function __construct(protected WebsiteService $service) {}

    /**
     * @OA\Get(path="/website/stats", summary="Statistiques du site web", tags={"Website"}, security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function stats(): JsonResponse
    {
        return $this->success($this->service->stats());
    }

    // ─── Gestion des demandes de réservation publiques ────────────────────────

    /**
     * @OA\Get(path="/website/reservations", summary="Demandes de réservation publiques", tags={"Website"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending","approved","rejected","converted"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function reservations(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'vehicle_id']);
        $data    = $this->service->listWebReservations($filters, $request->integer('per_page', 15));
        return $this->paginated($data, WebReservationResource::class);
    }

    /**
     * @OA\Get(path="/website/reservations/{id}", summary="Détail d'une demande", tags={"Website"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function showReservation(string $id): JsonResponse
    {
        return $this->success(new WebReservationResource($this->service->findWebReservation($id)));
    }

    /**
     * @OA\Patch(path="/website/reservations/{id}/approve", summary="Approuver une demande (crée une réservation)", tags={"Website"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Approved and reservation created")
     * )
     */
    public function approveReservation(Request $request, string $id): JsonResponse
    {
        $extra  = $request->only(['deposit_amount', 'notes']);
        $webRes = $this->service->approve($id, $extra);
        return $this->success(new WebReservationResource($webRes), 'Demande approuvée — réservation créée');
    }

    /**
     * @OA\Patch(path="/website/reservations/{id}/reject", summary="Rejeter une demande", tags={"Website"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"reason"},
     *     @OA\Property(property="reason", type="string")
     *   )),
     *   @OA\Response(response=200, description="Rejected")
     * )
     */
    public function rejectReservation(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $webRes = $this->service->reject($id, $request->reason);
        return $this->success(new WebReservationResource($webRes), 'Demande rejetée');
    }

    // ─── Gestion des véhicules affichés sur le site ───────────────────────────

    /**
     * @OA\Get(path="/website/vehicles", summary="Véhicules avec visibilité site web", tags={"Website"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="show_on_website", in="query", @OA\Schema(type="boolean")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function vehicles(Request $request): JsonResponse
    {
        $filters = $request->only(['agency_id', 'show_on_website']);
        if ($request->has('show_on_website')) {
            $filters['show_on_website'] = filter_var($request->show_on_website, FILTER_VALIDATE_BOOLEAN);
        }
        $data = $this->service->websiteVehicles($filters, $request->integer('per_page', 15));
        return $this->paginated($data, VehicleResource::class);
    }

    /**
     * @OA\Patch(path="/website/vehicles/{id}", summary="Mettre à jour visibilité/prix site web d'un véhicule", tags={"Website"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     @OA\Property(property="show_on_website", type="boolean"),
     *     @OA\Property(property="website_description", type="string"),
     *     @OA\Property(property="website_price_override", type="number")
     *   )),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function updateVehicle(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'show_on_website'        => 'nullable|boolean',
            'website_description'    => 'nullable|string',
            'website_price_override' => 'nullable|numeric|min:0',
        ]);
        $vehicle = $this->service->updateVehicleWebsite($id, $data);
        return $this->success(new VehicleResource($vehicle), 'Véhicule mis à jour');
    }
}
