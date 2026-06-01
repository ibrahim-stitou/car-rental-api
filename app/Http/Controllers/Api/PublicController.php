<?php

namespace App\Http\Controllers\Api;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Setting\Services\SettingService;
use App\Modules\Website\Resources\WebReservationResource;
use App\Modules\Website\Services\WebsiteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends BaseController
{
    public function __construct(
        protected WebsiteService $websiteService,
        protected SettingService $settingService,
    ) {}

    /**
     * @OA\Get(path="/public/company", summary="Informations publiques de la société", tags={"Public"},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function company(): JsonResponse
    {
        return $this->success($this->settingService->getPublicSettings());
    }

    /**
     * @OA\Get(path="/public/vehicles", summary="Véhicules disponibles pour le site web", tags={"Public"},
     *   @OA\Parameter(name="category", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="min_price", in="query", @OA\Schema(type="number")),
     *   @OA\Parameter(name="max_price", in="query", @OA\Schema(type="number")),
     *   @OA\Parameter(name="seats", in="query", @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=12)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function vehicles(Request $request): JsonResponse
    {
        $filters = $request->only(['category', 'min_price', 'max_price', 'seats']);
        $data    = $this->websiteService->publicVehicles($filters, $request->integer('per_page', 12));

        // Build minimal public payload (no internal data)
        $items = $data->map(fn($v) => [
            'id'                  => $v->id,
            'full_name'           => $v->full_name,
            'brand'               => $v->brand,
            'model'               => $v->model,
            'year'                => $v->year,
            'category'            => $v->category,
            'fuel_type'           => $v->fuel_type,
            'transmission'        => $v->transmission,
            'seats'               => $v->seats,
            'color'               => $v->color,
            'price_per_day'       => $v->website_price_override ?? $v->daily_rate,
            'website_description' => $v->website_description,
            'agency_city'         => $v->agency?->city,
            'photos'              => $v->getMediaByCollection('photos'),
            'thumb'               => $v->getFirstMediaUrl('photos', 'thumb'),
        ]);

        return $this->success([
            'data'         => $items,
            'current_page' => $data->currentPage(),
            'last_page'    => $data->lastPage(),
            'total'        => $data->total(),
            'per_page'     => $data->perPage(),
        ]);
    }

    /**
     * @OA\Get(path="/public/vehicles/{id}", summary="Détail d'un véhicule pour le site", tags={"Public"},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function vehicle(string $id): JsonResponse
    {
        $v = $this->websiteService->publicVehicle($id);

        return $this->success([
            'id'                  => $v->id,
            'full_name'           => $v->full_name,
            'brand'               => $v->brand,
            'model'               => $v->model,
            'year'                => $v->year,
            'category'            => $v->category,
            'fuel_type'           => $v->fuel_type,
            'transmission'        => $v->transmission,
            'seats'               => $v->seats,
            'color'               => $v->color,
            'price_per_day'       => $v->website_price_override ?? $v->daily_rate,
            'deposit_amount'      => $v->deposit_amount,
            'website_description' => $v->website_description,
            'agency'              => [
                'name'    => $v->agency?->name,
                'city'    => $v->agency?->city,
                'phone'   => $v->agency?->phone,
                'address' => $v->agency?->address,
            ],
            'photos'              => $v->getMediaByCollection('photos'),
        ]);
    }

    /**
     * @OA\Post(path="/public/reservations", summary="Soumettre une demande de réservation", tags={"Public"},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"vehicle_id","first_name","last_name","email","phone","pickup_date","return_date"},
     *     @OA\Property(property="vehicle_id", type="string", format="uuid"),
     *     @OA\Property(property="first_name", type="string"),
     *     @OA\Property(property="last_name", type="string"),
     *     @OA\Property(property="email", type="string", format="email"),
     *     @OA\Property(property="phone", type="string"),
     *     @OA\Property(property="pickup_date", type="string", format="date-time"),
     *     @OA\Property(property="return_date", type="string", format="date-time"),
     *     @OA\Property(property="pickup_location", type="string"),
     *     @OA\Property(property="message", type="string")
     *   )),
     *   @OA\Response(response=201, description="Request submitted"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function submitReservation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vehicle_id'      => 'required|uuid|exists:vehicles,id',
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'email'           => 'required|email|max:200',
            'phone'           => 'required|string|max:30',
            'pickup_date'     => 'required|date|after:now',
            'return_date'     => 'required|date|after:pickup_date',
            'pickup_location' => 'nullable|string|max:200',
            'message'         => 'nullable|string|max:1000',
        ]);

        $data['ip_address'] = $request->ip();

        $webRes = $this->websiteService->submitReservation($data);
        $webRes->load('vehicle');

        return $this->created(new WebReservationResource($webRes), 'Votre demande a bien été soumise. Nous vous contacterons sous peu.');
    }
}
