<?php

namespace App\Modules\Agency\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Models\AgencyDocumentCounter;
use App\Models\Expense;
use App\Models\Reservation;
use App\Modules\Agency\Requests\StoreAgencyRequest;
use App\Modules\Agency\Requests\UpdateAgencyRequest;
use App\Modules\Agency\Resources\AgencyResource;
use App\Modules\Agency\Services\AgencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AgencyController extends BaseController
{
    private const COUNTER_TYPES = ['fa', 'av', 'dv', 'bc', 'bl', 'br', 'lld'];

    public function __construct(protected AgencyService $service)
    {
    }

    /**
     * Document numbering counters for this agency — one row per document
     * type (FA/AV/DV/BC/BL/BR/LLD), each agency's own independent sequence.
     */
    public function counters(string $id): JsonResponse
    {
        $agency = $this->service->find($id);

        $existing = AgencyDocumentCounter::where('agency_id', $agency->id)->get()->keyBy('document_type');

        $result = collect(self::COUNTER_TYPES)->map(function ($type) use ($existing) {
            $counter = $existing->get($type);
            return [
                'document_type' => $type,
                'prefix'        => $counter->prefix ?? strtoupper($type),
                'separator'     => $counter->separator ?? '-',
                'digits'        => $counter->digits ?? 6,
                'current'       => $counter->current ?? 0,
            ];
        })->values();

        return $this->success($result);
    }

    public function updateCounters(Request $request, string $id): JsonResponse
    {
        $agency = $this->service->find($id);

        $data = $request->validate([
            'counters'                 => 'required|array',
            'counters.*.document_type' => ['required', 'string', Rule::in(self::COUNTER_TYPES)],
            'counters.*.prefix'        => 'required|string|max:20',
            'counters.*.separator'     => 'nullable|string|max:5',
            'counters.*.digits'        => 'required|integer|min:1|max:10',
            'counters.*.current'       => 'required|integer|min:0',
        ]);

        foreach ($data['counters'] as $row) {
            AgencyDocumentCounter::updateOrCreate(
                ['agency_id' => $agency->id, 'document_type' => $row['document_type']],
                [
                    'prefix'    => $row['prefix'],
                    'separator' => $row['separator'] ?? '-',
                    'digits'    => $row['digits'],
                    'current'   => $row['current'],
                ]
            );
        }

        $updated = AgencyDocumentCounter::where('agency_id', $agency->id)->get()->keyBy('document_type');
        $result = collect(self::COUNTER_TYPES)->map(fn($type) => $updated->get($type))->values();

        return $this->success($result, 'Compteurs mis à jour');
    }

    /**
     * @OA\Get(
     *   path="/agencies",
     *   summary="Liste des agences",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="city", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="is_active", in="query", @OA\Schema(type="boolean")),
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"name","created_at"})),
     *   @OA\Parameter(name="sort_dir", in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->filled('search')) {
            $data = $this->service->search($request->search, $request->integer('per_page', 15));
            return $this->paginated($data, AgencyResource::class);
        }

        $filters = $request->only(['city', 'is_active', 'name']);
        $data = $this->service->list(
            $filters,
            $request->integer('per_page', 15),
            $request->input('sort_by', 'created_at'),
            $request->input('sort_dir', 'desc')
        );

        return $this->paginated($data, AgencyResource::class);
    }

    /**
     * @OA\Post(
     *   path="/agencies",
     *   summary="Créer une agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"name","email"},
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="email", type="string", format="email"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="city", type="string"),
     *     @OA\Property(property="country", type="string"),
     *     @OA\Property(property="phone", type="string"),
     *     @OA\Property(property="manager_id", type="string", format="uuid")
     *   )),
     *   @OA\Response(response=201, description="Agency created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreAgencyRequest $request): JsonResponse
    {
        $agency = $this->service->create($request->validated());
        return $this->created(new AgencyResource($agency), 'Agency created successfully');
    }

    /**
     * @OA\Get(
     *   path="/agencies/{id}",
     *   summary="Détails d'une agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $agency = $this->service->find($id);
        return $this->success(new AgencyResource($agency));
    }

    /**
     * @OA\Put(
     *   path="/agencies/{id}",
     *   summary="Modifier une agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="email", type="string", format="email"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="city", type="string"),
     *     @OA\Property(property="phone", type="string")
     *   )),
     *   @OA\Response(response=200, description="Agency updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateAgencyRequest $request, string $id): JsonResponse
    {
        $agency = $this->service->update($id, $request->validated());
        return $this->success(new AgencyResource($agency), 'Agency updated successfully');
    }

    /**
     * @OA\Delete(
     *   path="/agencies/{id}",
     *   summary="Supprimer une agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\Response(response=200, description="Agency deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Agency deleted successfully');
    }

    /**
     * @OA\Post(
     *   path="/agencies/{id}/logo",
     *   summary="Upload logo de l'agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="logo", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Logo uploaded")
     * )
     */
    public function uploadLogo(Request $request, string $id): JsonResponse
    {
        $request->validate(['logo' => 'required|image|mimes:jpeg,png,webp|max:5120']);
        $agency = $this->service->find($id);
        $agency->uploadMedia($request->file('logo'), 'logo');
        return $this->success(['logo' => $agency->getFirstMediaUrl('logo')], 'Logo uploaded successfully');
    }

    /**
     * @OA\Post(
     *   path="/agencies/{id}/documents",
     *   summary="Upload documents de l'agence",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="documents[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Documents uploaded")
     * )
     */
    public function uploadDocuments(Request $request, string $id): JsonResponse
    {
        $request->validate(['documents' => 'required|array', 'documents.*' => 'file|max:10240']);
        $agency = $this->service->find($id);
        $agency->uploadMultipleMedia($request->file('documents'), 'documents');
        return $this->success($agency->getMediaByCollection('documents'), 'Documents uploaded successfully');
    }

    public function deleteMedia(string $id, int $mediaId): JsonResponse
    {
        $agency = $this->service->find($id);
        $media = $agency->media()->findOrFail($mediaId);
        $media->delete();
        return $this->success(null, 'Media deleted successfully');
    }

    public function vehicles(string $id): JsonResponse
    {
        $agency = $this->service->find($id);
        return $this->success($agency->vehicles);
    }

    /**
     * @OA\Post(
     *   path="/agencies/{id}/restore",
     *   summary="Restaurer une agence supprimée",
     *   tags={"Agencies"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Agency restored")
     * )
     */
    public function restore(string $id): JsonResponse
    {
        $agency = $this->service->restore($id);
        return $this->success(new AgencyResource($agency), 'Agency restored successfully');
    }

    public function statistics(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $agency = $this->service->find($id);

        $vq = $agency->vehicles();
     // Migrated reservations are a pure historical archive (forced to
        // completed/paid regardless of their real original status) and must
        // not inflate revenue/credit/count KPIs.
        $rq = $agency->reservations()->whereNull('legacy_id');

        $totalRevenue = (float) (clone $rq)->whereIn('status', ['completed'])
            ->join('reservation_payments', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->when($startDate, fn($q) => $q->whereDate('reservation_payments.payment_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('reservation_payments.payment_date', '<=', $endDate))
            ->sum('reservation_payments.amount');

        // Outstanding credit is a point-in-time balance ("as of today"), not filtered by the period.
        $creditReservations = $this->creditReservationsQuery($rq);

        $totalCredit = $creditReservations->sum('credit_amount');
        $totalExpenses = (float) Expense::where('agency_id', $id)
            ->when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->sum('amount');

        return $this->success([
            'agency'  => new AgencyResource($agency),
            'vehicles' => [
                'total'          => (clone $vq)->count(),
                'available'      => (clone $vq)->where('status', 'available')->count(),
                'rented'         => (clone $vq)->where('status', 'rented')->count(),
                'maintenance'    => (clone $vq)->where('status', 'maintenance')->count(),
                'out_of_service' => (clone $vq)->where('status', 'out_of_service')->count(),
            ],
            'reservations' => [
                'total'     => (clone $rq)->count(),
                'pending'   => (clone $rq)->where('status', 'pending')->count(),
                'confirmed' => (clone $rq)->where('status', 'confirmed')->count(),
                'active'    => (clone $rq)->where('status', 'active')->count(),
                'completed' => (clone $rq)->where('status', 'completed')->count(),
                'cancelled' => (clone $rq)->where('status', 'cancelled')->count(),
                'overdue'   => (clone $rq)->where('status', 'active')->where('return_date', '<', now())->count(),
                'this_month' => (clone $rq)->whereMonth('reservations.created_at', now()->month)->whereYear('reservations.created_at', now()->year)->count(),
            ],
            'financials' => [
                'total_revenue'  => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_revenue'    => $totalRevenue - $totalExpenses,
                'total_credit'   => (float) $totalCredit,
                'credit_count'   => $creditReservations->count(),
                'period'         => [
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                ],
            ],
            'clients' => [
                'total'       => $agency->clients()->count(),
                'blacklisted' => $agency->clients()->where('is_blacklisted', true)->count(),
            ],
        ]);
    }

    /**
     * Lists the reservations (contracts) that make up this agency's outstanding
     * client credit — contracts are the only source of credit, so this is the
     * detail view behind the "Crédit client" KPI.
     */
    public function credits(string $id): JsonResponse
    {
        $agency = $this->service->find($id);
        $creditRows = $this->creditReservationsQuery($agency->reservations()->whereNull('legacy_id'));

        $reservations = Reservation::whereIn('id', $creditRows->pluck('id'))
            ->with(['client:id,first_name,last_name', 'vehicle:id,brand,model,registration_number'])
            ->get()
            ->keyBy('id');

        $result = $creditRows->map(function ($row) use ($reservations) {
            $reservation = $reservations->get($row->id);
            return [
                'id'                 => $row->id,
                'reservation_number' => $row->reservation_number,
                'status'             => $reservation?->status,
                'pickup_date'        => $reservation?->pickup_date,
                'return_date'        => $reservation?->return_date,
                'client'             => $reservation?->client,
                'vehicle'            => $reservation?->vehicle,
                'total_amount'       => (float) $row->total_amount,
                'paid_amount'        => (float) $row->paid_amount,
                'credit_amount'      => (float) $row->credit_amount,
            ];
        })->sortByDesc('credit_amount')->values();

        return $this->success($result);
    }

    /**
     * Reservations (of the given agency-scoped query) whose paid amount is
     * still short of their total — the sole source of client credit.
     */
    private function creditReservationsQuery($reservationsQuery)
    {
        return (clone $reservationsQuery)->whereIn('status', ['completed', 'active'])
            ->selectRaw('reservations.id, reservation_number, total_amount, COALESCE(SUM(rp.amount),0) as paid_amount, total_amount - COALESCE(SUM(rp.amount),0) as credit_amount')
            ->leftJoin('reservation_payments as rp', 'reservations.id', '=', 'rp.reservation_id')
            ->groupBy('reservations.id', 'reservation_number', 'total_amount')
            ->havingRaw('credit_amount > 0')
            ->get();
    }
}

