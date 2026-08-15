<?php

namespace App\Modules\Reservation\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Reservation\Requests\StoreReservationRequest;
use App\Modules\Reservation\Requests\UpdateReservationRequest;
use App\Modules\Reservation\Resources\ReservationResource;
use App\Modules\Reservation\Services\ReservationService;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends BaseController
{
    public function __construct(
        protected ReservationService $service,
        protected PdfService $pdfService,
    ) {}

    /**
     * @OA\Get(path="/reservations", summary="Liste des réservations", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="vehicle_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="client_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending","confirmed","active","completed","cancelled","no_show"})),
     *   @OA\Parameter(name="payment_status", in="query", @OA\Schema(type="string", enum={"pending","partial","paid","refunded"})),
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="date_from", in="query", description="Période : réservations dont le séjour chevauche cette date de début", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="date_to", in="query", description="Période : réservations dont le séjour chevauche cette date de fin", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */

    public function index(Request $request): JsonResponse
    {
        $rawFilters = $request->only([
            'agency_id', 'vehicle_id', 'client_id', 'status',
            'payment_status', 'overdue', 'search', 'date_from',
            'date_to', 'legacy_id', 'rental_unit'
        ]);

        $filters = $this->prepareFilters($rawFilters);

        $data = $this->service->list($filters, $request->integer('per_page', 15));

        return $this->paginated($data, ReservationResource::class);
    }

    private function prepareFilters(array $filters): array
    {
        return array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * @OA\Post(path="/reservations", summary="Créer une réservation", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"agency_id","vehicle_id","client_id","pickup_date","return_date","daily_rate"},
     *     @OA\Property(property="agency_id", type="string", format="uuid"),
     *     @OA\Property(property="vehicle_id", type="string", format="uuid"),
     *     @OA\Property(property="client_id", type="string", format="uuid"),
     *     @OA\Property(property="pickup_date", type="string", format="date-time"),
     *     @OA\Property(property="return_date", type="string", format="date-time"),
     *     @OA\Property(property="pickup_location", type="string"),
     *     @OA\Property(property="return_location", type="string"),
     *     @OA\Property(property="daily_rate", type="number"),
     *     @OA\Property(property="discount_percentage", type="number"),
     *     @OA\Property(property="additional_fees", type="number"),
     *     @OA\Property(property="deposit_amount", type="number"),
     *     @OA\Property(property="payment_method", type="string"),
     *     @OA\Property(property="notes", type="string")
     *   )),
     *   @OA\Response(response=201, description="Reservation created"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->service->create($request->validated());
        return $this->created(new ReservationResource($reservation->load(['agency', 'vehicle', 'client'])), 'Reservation created');
    }

    /**
     * @OA\Get(path="/reservations/{id}", summary="Détails d'une réservation", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        return $this->success(new ReservationResource($this->service->find($id)));
    }

    /**
     * @OA\Put(path="/reservations/{id}", summary="Modifier une réservation", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(UpdateReservationRequest $request, string $id): JsonResponse
    {
        $reservation = $this->service->update($id, $request->validated());
        return $this->success(new ReservationResource($reservation), 'Reservation updated');
    }

    /**
     * @OA\Delete(path="/reservations/{id}", summary="Supprimer une réservation", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Reservation deleted');
    }

    /**
     * @OA\Patch(path="/reservations/{id}/confirm", summary="Confirmer une réservation", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Reservation confirmed")
     * )
     */
    public function confirm(string $id): JsonResponse
    {
        $reservation = $this->service->confirm($id);
        // Store who validated
        $reservation->update(['validated_by' => auth()->id()]);
        // Ensure the contract is locked with the validator's stamp/signature —
        // generates it the first time, or re-validates it if a prior
        // extension/edit had marked it invalidated.
        $reservation = $this->service->ensureContractValid($reservation->fresh(['agency', 'vehicle', 'client', 'validator']));
        return $this->success(new ReservationResource($reservation), 'Reservation confirmed');
    }

    /**
     * @OA\Patch(path="/reservations/{id}/activate", summary="Activer une réservation (départ)", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(
     *     @OA\Property(property="initial_mileage", type="integer"),
     *     @OA\Property(property="fuel_level_pickup", type="string", enum={"empty","quarter","half","three_quarters","full"})
     *   )),
     *   @OA\Response(response=200, description="Activated")
     * )
     */
    public function activate(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'initial_mileage'   => 'nullable|integer|min:0',
            'fuel_level_pickup' => 'nullable|in:empty,quarter,half,three_quarters,full',
        ]);
        $reservation = $this->service->activate($id, $data);
        return $this->success(new ReservationResource($reservation), 'Reservation activated (pickup done)');
    }

    /**
     * @OA\Patch(path="/reservations/{id}/complete", summary="Compléter une réservation (retour)", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(
     *     @OA\Property(property="final_mileage", type="integer"),
     *     @OA\Property(property="fuel_level_return", type="string", enum={"empty","quarter","half","three_quarters","full"}),
     *     @OA\Property(property="additional_fees", type="number")
     *   )),
     *   @OA\Response(response=200, description="Completed")
     * )
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'final_mileage'           => 'nullable|integer|min:0',
            'fuel_level_return'       => 'nullable|in:empty,quarter,half,three_quarters,full',
            'additional_fees'         => 'nullable|numeric|min:0',
            'actual_return_date'      => 'nullable|date',
            'actual_return_location'  => 'nullable|string|max:255',
            'is_favorable'            => 'nullable|boolean',
            'closure_comment'         => 'nullable|string',
            'final_total_amount'      => 'nullable|numeric|min:0',
        ]);
        $reservation = $this->service->complete($id, $data);
        return $this->success(new ReservationResource($reservation), 'Reservation completed (return done)');
    }

    /**
     * @OA\Get(path="/reservations/{id}/early-return-preview", summary="Aperçu du montant en cas de retour anticipé", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="actual_return_date", in="query", required=true, @OA\Schema(type="string", format="date")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function earlyReturnPreview(Request $request, string $id): JsonResponse
    {
        $request->validate(['actual_return_date' => 'required|date']);
        $reservation = $this->service->find($id);
        $preview = $reservation->previewEarlyReturn(\Carbon\Carbon::parse($request->actual_return_date));
        return $this->success($preview);
    }

    /**
     * @OA\Patch(path="/reservations/{id}/cancel", summary="Annuler une réservation", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=false, @OA\JsonContent(
     *     @OA\Property(property="reason", type="string")
     *   )),
     *   @OA\Response(response=200, description="Cancelled")
     * )
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string']);
        $reservation = $this->service->cancel($id, $request->reason);
        return $this->success(new ReservationResource($reservation), 'Reservation cancelled');
    }

    /**
     * @OA\Get(path="/reservations/calendar", summary="Vue calendrier", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="start_date", in="query", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="end_date", in="query", @OA\Schema(type="string", format="date")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function calendar(Request $request): JsonResponse
    {
        $filters = $request->only(['agency_id', 'start_date', 'end_date']);
        $data = $this->service->calendar($filters);
        return $this->success(ReservationResource::collection($data));
    }

    /**
     * @OA\Get(path="/reservations/overdue", summary="Réservations en retard", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function overdue(Request $request): JsonResponse
    {
        return $this->paginated($this->service->overdue($request->integer('per_page', 15)), ReservationResource::class);
    }

    /**
     * @OA\Get(path="/reservations/statistics", summary="Statistiques réservations", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="agency_id", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        return $this->success($this->service->statistics($request->only(['agency_id'])));
    }

    /**
     * @OA\Post(path="/reservations/{id}/contract", summary="Upload contrat PDF", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="contract", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadContract(Request $request, string $id): JsonResponse
    {
        $request->validate(['contract' => 'required|file|mimes:pdf|max:10240']);
        $reservation = $this->service->find($id);
        $reservation->uploadMedia($request->file('contract'), 'contract');
        return $this->success(['url' => $reservation->getFirstMediaUrl('contract')], 'Contract uploaded');
    }

    /**
     * @OA\Post(path="/reservations/{id}/pickup-photos", summary="Upload photos départ", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="photos[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadPickupPhotos(Request $request, string $id): JsonResponse
    {
        $request->validate(['photos' => 'required|array|max:20', 'photos.*' => 'image|mimes:jpeg,png,webp|max:5120']);
        $reservation = $this->service->find($id);
        $reservation->uploadMultipleMedia($request->file('photos'), 'pickup_photos');
        return $this->success($reservation->getMediaByCollection('pickup_photos'), 'Pickup photos uploaded');
    }

    /**
     * @OA\Post(path="/reservations/{id}/return-photos", summary="Upload photos retour", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="photos[]", type="array", @OA\Items(type="string", format="binary")))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadReturnPhotos(Request $request, string $id): JsonResponse
    {
        $request->validate(['photos' => 'required|array|max:20', 'photos.*' => 'image|mimes:jpeg,png,webp|max:5120']);
        $reservation = $this->service->find($id);
        $reservation->uploadMultipleMedia($request->file('photos'), 'return_photos');
        return $this->success($reservation->getMediaByCollection('return_photos'), 'Return photos uploaded');
    }

    /**
     * @OA\Post(path="/reservations/{id}/damage-report", summary="Upload rapport dommages", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="damage_report", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadDamageReport(Request $request, string $id): JsonResponse
    {
        $request->validate(['damage_report' => 'required|file|max:10240']);
        $reservation = $this->service->find($id);
        $reservation->uploadMedia($request->file('damage_report'), 'damage_reports');
        return $this->success($reservation->getMediaByCollection('damage_reports'), 'Damage report uploaded');
    }

    /**
     * @OA\Post(path="/reservations/{id}/documents", summary="Ajouter des documents (avec titre)", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *       @OA\Property(property="documents[]", type="array", @OA\Items(type="string", format="binary")),
     *       @OA\Property(property="names[]", type="array", @OA\Items(type="string"))
     *     )
     *   )),
     *   @OA\Response(response=200, description="Uploaded")
     * )
     */
    public function uploadDocuments(Request $request, string $id): JsonResponse
    {
        $request->validate(['documents' => 'required|array', 'documents.*' => 'file|max:10240']);
        $reservation = $this->service->find($id);
        $reservation->uploadMultipleMedia($request->file('documents'), 'documents', $request->input('names'));
        return $this->success($reservation->getMediaByCollection('documents'), 'Documents téléversés');
    }

    /**
     * @OA\Delete(path="/reservations/{id}/media/{mediaId}", summary="Supprimer un média", tags={"Reservations"}, security={{"bearerAuth":{}}},
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
     * @OA\Get(path="/reservations/{id}/invoice", summary="Informations de facturation", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Invoice data")
     * )
     */
    public function generateInvoice(string $id): JsonResponse
    {
        $reservation = $this->service->find($id);
        return $this->success(new ReservationResource($reservation));
    }

    /**
     * @OA\Get(path="/reservations/{id}/contract/pdf", summary="Visualiser le contrat en PDF", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Parameter(name="download", in="query", @OA\Schema(type="boolean", default=false)),
     *   @OA\Response(response=200, description="PDF stream")
     * )
     */
    public function contractPdf(Request $request, string $id): Response
    {
        $reservation = $this->service->find($id);

        // No-cache: this URL is stable (keyed only by reservation id) but the
        // file behind it isn't — regenerating a contract swaps the stored
        // media without changing the URL, and Symfony's BinaryFileResponse
        // (which response()->file()/download() build on) auto-sets
        // Last-Modified from the file's mtime, which is enough for browsers
        // to serve a stale cached copy of the *previous* file on this same
        // URL unless caching is explicitly forbidden here.
        $noCache = ['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0', 'Pragma' => 'no-cache'];

        // Once locked, always serve the stored file as-is — never regenerate
        // from current (possibly since-edited) reservation data.
        if ($reservation->contract_generated_at && ($media = $reservation->getFirstMedia('contract'))) {
            $filename = 'contract-' . $reservation->reservation_number . '.pdf';
            return $request->boolean('download')
                ? response()->download($media->getPath(), $filename, $noCache)
                : response()->file($media->getPath(), array_merge(['Content-Type' => 'application/pdf'], $noCache));
        }

        return $this->pdfService->generateReservationContract($reservation, $request->boolean('download'))
            ->withHeaders($noCache);
    }

    /**
     * @OA\Get(path="/reservations/{id}/contract/pdf/save", summary="Générer et sauvegarder le contrat PDF dans la médiathèque", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="URL du PDF sauvegardé")
     * )
     */
    public function saveContractPdf(string $id): JsonResponse
    {
        $reservation = $this->service->find($id);
        $reservation = $this->service->generateAndLockContract($reservation);
        return $this->success(['url' => $reservation->getFirstMediaUrl('contract')], 'Contract PDF generated and saved');
    }

    /**
     * @OA\Post(path="/reservations/{id}/restore", summary="Restaurer une réservation", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Restored")
     * )
     */
    public function noShow(string $id): JsonResponse
    {
        $reservation = $this->service->noShow($id);
        return $this->success(new ReservationResource($reservation), 'Réservation marquée non présentée');
    }

    public function restore(string $id): JsonResponse
    {
        $reservation = $this->service->restore($id);
        return $this->success(new ReservationResource($reservation), 'Reservation restored');
    }

    /**
     * Check if a vehicle has conflicting reservations in the given period.
     * Returns any non-completed reservation overlapping the interval.
     */
    public function checkConflict(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id'  => 'required|uuid',
            'pickup_date' => 'required|date',
            'return_date' => 'required|date|after:pickup_date',
            'exclude_id'  => 'nullable|uuid',
        ]);

        $conflict = \App\Models\Reservation::query()
            ->where('vehicle_id', $request->vehicle_id)
            ->whereNotIn('status', ['cancelled', 'no_show', 'completed'])
            ->where(function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('pickup_date', '<', $request->return_date)
                       ->where('return_date', '>', $request->pickup_date);
                });
            })
            ->when($request->exclude_id, fn($q) => $q->where('id', '!=', $request->exclude_id))
            ->with(['client:id,first_name,last_name,phone', 'vehicle:id,brand,model,registration_number'])
            ->first();

        if (!$conflict) {
            return $this->success(['has_conflict' => false]);
        }

        return $this->success([
            'has_conflict' => true,
            'conflict'     => [
                'id'                 => $conflict->id,
                'reservation_number' => $conflict->reservation_number,
                'status'             => $conflict->status,
                'pickup_date'        => $conflict->pickup_date?->toISOString(),
                'return_date'        => $conflict->return_date?->toISOString(),
                'client'             => $conflict->client ? [
                    'full_name' => $conflict->client->full_name,
                    'phone'     => $conflict->client->phone,
                ] : null,
            ],
        ]);
    }

    /**
     * Extend a reservation's return date. This always sends the reservation
     * back to 'pending' — even if it was already active — and, if a contract
     * had been generated, invalidates it: an extension changes the terms the
     * client is bound by, so it must go through the normal
     * confirm (re-sign/re-stamp) → activate cycle again rather than silently
     * keep running on stale paperwork. The car itself isn't touched
     * (vehicle.status stays as-is) since it's still physically with the client.
     */
    public function extend(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'new_return_date' => 'required|date',
            'additional_fees' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
            // Optional rate adjustment at extension time (e.g. a negotiated
            // discount for a repeat/long-standing client) — only the field
            // matching the reservation's own rental_unit actually affects
            // billing (see Reservation::calculateTotal()), the others are
            // harmlessly ignored if sent.
            'daily_rate'      => 'nullable|numeric|min:0',
            'hourly_rate'     => 'nullable|numeric|min:0',
            'monthly_rate'    => 'nullable|numeric|min:0',
        ]);

        $reservation = $this->service->find($id);

        if (!in_array($reservation->status, ['pending', 'confirmed', 'active'])) {
            return $this->error('Cette réservation ne peut pas être prolongée (statut: ' . $reservation->status . ')', 422);
        }

        $newReturnDate = \Carbon\Carbon::parse($data['new_return_date']);

        if ($newReturnDate->lte($reservation->return_date)) {
            return $this->error('La nouvelle date de retour doit être postérieure à la date actuelle.', 422);
        }

        // Conflict check for extension
        $conflict = \App\Models\Reservation::query()
            ->where('vehicle_id', $reservation->vehicle_id)
            ->where('id', '!=', $reservation->id)
            ->whereNotIn('status', ['cancelled', 'no_show', 'completed'])
            ->where('pickup_date', '<', $newReturnDate)
            ->where('return_date', '>', $reservation->return_date)
            ->first();

        if ($conflict) {
            return $this->error(
                "Conflit détecté avec la réservation {$conflict->reservation_number} du " .
                $conflict->pickup_date->format('d/m/Y') . " au " .
                $conflict->return_date->format('d/m/Y'), 422
            );
        }

        // Recalculate
        $reservation->return_date = $newReturnDate;
        foreach (['daily_rate', 'hourly_rate', 'monthly_rate'] as $rateField) {
            if (array_key_exists($rateField, $data) && $data[$rateField] !== null) {
                $reservation->{$rateField} = $data[$rateField];
            }
        }
        if (!empty($data['additional_fees'])) {
            $reservation->additional_fees = ($reservation->additional_fees ?? 0) + $data['additional_fees'];
        }
        if (!empty($data['notes'])) {
            $reservation->notes = ($reservation->notes ? $reservation->notes . "\n" : '') . '[Prolongation] ' . $data['notes'];
        }
        $reservation->status = 'pending';
        $reservation->validated_by = null;
        $reservation->calculateTotal();
        $reservation->save();

        $this->service->invalidateContract($reservation, 'Prolongation de la réservation');

        return $this->success(new ReservationResource($reservation->fresh(['vehicle', 'client', 'agency'])), 'Réservation prolongée avec succès — à reconfirmer');
    }

    /**
     * @OA\Patch(path="/reservations/{id}/contract/invalidate", summary="Dévalider le contrat", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(required={"reason"}, @OA\Property(property="reason", type="string"))),
     *   @OA\Response(response=200, description="Contract invalidated")
     * )
     */
    public function invalidateContract(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|min:5|max:500']);

        $reservation = $this->service->find($id);
        if ($reservation->contract_status !== 'valid') {
            return $this->error("Seul un contrat valide peut être dévalidé (statut actuel : {$reservation->contract_status}).", 422);
        }

        $reservation = $this->service->invalidateContract($reservation, $request->reason);
        return $this->success(new ReservationResource($reservation->load('contractEvents.actor')), 'Contrat dévalidé');
    }

    /**
     * @OA\Patch(path="/reservations/{id}/contract/regenerate", summary="Régénérer le contrat", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Contract regenerated")
     * )
     */
    public function regenerateContract(string $id): JsonResponse
    {
        $reservation = $this->service->revalidateContract($id);
        return $this->success(new ReservationResource($reservation->load('contractEvents.actor')), 'Contrat régénéré');
    }

    /**
     * @OA\Get(path="/reservations/{id}/contract/history", summary="Historique de validation du contrat", tags={"Reservations"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function contractHistory(string $id): JsonResponse
    {
        $reservation = $this->service->find($id)->load('contractEvents.actor');
        return $this->success((new ReservationResource($reservation))->toArray(request())['contract_events']);
    }

    public function credits(Request $request): JsonResponse
    {
        $perPage  = $request->integer('per_page', 15);
        $agencyId = $request->query('agency_id');

        // LLD (rental_unit = 'month') contracts are never owed in full up
        // front — only the months due so far (first month at signing, +1 per
        // whole month elapsed since, capped at the contract length; mirrors
        // Reservation::getAmountDueSoFarAttribute()). A 24-month, 96 000 MAD
        // contract must show a 4 000 MAD credit at signing, not 96 000.
        $amountBasis = "CASE WHEN reservations.rental_unit = 'month'
            THEN LEAST(reservations.total_amount, reservations.monthly_rate * LEAST(reservations.total_months, 1 + TIMESTAMPDIFF(MONTH, reservations.pickup_date, NOW())))
            ELSE reservations.total_amount
        END";

        $credits = \App\Models\Reservation::query()
            // Migrated reservations are a pure historical archive (forced to
            // completed/paid regardless of their real original status) and
            // must not appear as outstanding client credit.
            ->whereNull('legacy_id')
            ->whereIn('status', ['completed', 'active'])
            ->when($agencyId, fn($q) => $q->where('agency_id', $agencyId))
            ->selectRaw("reservations.*, {$amountBasis} - COALESCE(SUM(rp.amount),0) as credit_amount")
            ->leftJoin('reservation_payments as rp', 'reservations.id', '=', 'rp.reservation_id')
            ->groupBy('reservations.id')
            ->havingRaw('credit_amount > 0')
            ->with(['vehicle:id,brand,model,registration_number', 'client:id,first_name,last_name,phone', 'agency:id,name'])
            ->orderByDesc('credit_amount')
            ->paginate($perPage);

        return $this->paginated($credits, null);
    }
}

