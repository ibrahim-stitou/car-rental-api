<?php

namespace App\Http\Controllers\Api;

use App\Core\Http\Controllers\BaseController;
use App\Models\BillingDocument;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Modules\Billing\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends BaseController
{
    public function __construct(protected BillingService $billingService) {}

    /**
     * Lister les paiements d'une réservation.
     */
    public function index(string $reservationId): JsonResponse
    {
        $reservation = Reservation::findOrFail($reservationId);

        $payments = $reservation->payments()
            ->with(['recorder:id,first_name,last_name', 'billingDocument:id,document_number,type,total_amount,status'])
            ->orderByDesc('payment_date')
            ->get();

        $totalPaid  = $payments->sum('amount');
        $balance    = $reservation->total_amount - $totalPaid;

        return $this->success([
            'payments'    => $payments,
            'total_amount' => (float) $reservation->total_amount,
            'total_paid'   => (float) $totalPaid,
            'balance'      => (float) $balance,
            'is_fully_paid' => $balance <= 0,
        ]);
    }

    /**
     * Enregistrer un paiement.
     */
    public function store(Request $request, string $reservationId): JsonResponse
    {
        $reservation = Reservation::findOrFail($reservationId);

        $validator = Validator::make($request->all(), [
            'amount'              => 'required|numeric|min:0.01',
            'payment_method'      => 'required|in:cash,card,bank_transfer,check,online',
            'payment_date'        => 'required|date',
            'reference'           => 'nullable|string|max:255',
            'notes'               => 'nullable|string',
            // Optional: settle a specific LLD invoice with this payment
            // instead of just paying down the reservation's general balance.
            'billing_document_id' => 'nullable|uuid|exists:billing_documents,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $totalPaid = (float) $reservation->payments()->sum('amount');
        $balance   = (float) $reservation->total_amount - $totalPaid;

        if ($balance <= 0) {
            return $this->error('Cette réservation est déjà entièrement payée.', 422);
        }

        if ((float) $request->amount > $balance) {
            return $this->validationError([
                'amount' => ["Le montant ne peut pas dépasser le solde restant (" . number_format($balance, 2) . " MAD)."],
            ]);
        }

        $billingDocument = null;
        if ($request->filled('billing_document_id')) {
            $billingDocument = BillingDocument::where('id', $request->billing_document_id)
                ->where('reservation_id', $reservationId)
                ->where('type', 'LLD')
                ->first();

            if (!$billingDocument) {
                return $this->validationError([
                    'billing_document_id' => ['Facture LLD introuvable pour cette réservation.'],
                ]);
            }
            if ($billingDocument->status === 'paid') {
                return $this->error('Cette facture est déjà marquée comme payée.', 422);
            }
            if (!in_array($billingDocument->status, ['approved', 'pending'], true)) {
                return $this->error('Cette facture doit être validée avant de pouvoir être payée.', 422);
            }
            // Paying off a specific invoice must fully settle it — no partial
            // invoice payments, so its status can flip cleanly to "payée".
            if (abs((float) $request->amount - (float) $billingDocument->total_amount) > 0.01) {
                return $this->validationError([
                    'amount' => ['Le paiement d\'une facture doit correspondre exactement à son montant total (' . number_format($billingDocument->total_amount, 2) . ' MAD).'],
                ]);
            }
        }

        $payment = ReservationPayment::create([
            'reservation_id'       => $reservationId,
            'billing_document_id'  => $billingDocument?->id,
            'recorded_by'          => Auth::id(),
            'amount'               => $request->amount,
            'payment_method'       => $request->payment_method,
            'payment_date'         => $request->payment_date,
            'reference'            => $request->reference,
            'notes'                => $request->notes,
        ]);

        $reservation->syncPaymentStatus();

        if ($billingDocument) {
            $this->billingService->markAsPaid($billingDocument->id, [
                'payment_method'    => $request->payment_method,
                'payment_reference' => $request->reference,
            ]);
        }

        return $this->created(
            $payment->load(['recorder:id,first_name,last_name', 'billingDocument:id,document_number,type,total_amount,status']),
            'Paiement enregistré'
        );
    }

    /**
     * Supprimer un paiement.
     */
    public function destroy(string $reservationId, string $paymentId): JsonResponse
    {
        $payment = ReservationPayment::where('reservation_id', $reservationId)->findOrFail($paymentId);
        $billingDocumentId = $payment->billing_document_id;
        $payment->delete();

        if ($billingDocumentId) {
            $this->billingService->revertPayment($billingDocumentId);
        }

        $reservation = Reservation::find($reservationId);
        $reservation?->syncPaymentStatus();

        return $this->success(null, 'Paiement supprimé');
    }
}
