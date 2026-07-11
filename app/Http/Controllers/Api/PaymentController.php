<?php

namespace App\Http\Controllers\Api;

use App\Core\Http\Controllers\BaseController;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends BaseController
{
    /**
     * Lister les paiements d'une réservation.
     */
    public function index(string $reservationId): JsonResponse
    {
        $reservation = Reservation::findOrFail($reservationId);

        $payments = $reservation->payments()
            ->with('recorder:id,first_name,last_name')
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
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,bank_transfer,check,online',
            'payment_date'   => 'required|date',
            'reference'      => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $payment = ReservationPayment::create([
            'reservation_id' => $reservationId,
            'recorded_by'    => Auth::id(),
            'amount'         => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date'   => $request->payment_date,
            'reference'      => $request->reference,
            'notes'          => $request->notes,
        ]);

        $reservation->syncPaymentStatus();

        return $this->created($payment->load('recorder:id,first_name,last_name'), 'Paiement enregistré');
    }

    /**
     * Supprimer un paiement.
     */
    public function destroy(string $reservationId, string $paymentId): JsonResponse
    {
        $payment = ReservationPayment::where('reservation_id', $reservationId)->findOrFail($paymentId);
        $payment->delete();

        $reservation = Reservation::find($reservationId);
        $reservation?->syncPaymentStatus();

        return $this->success(null, 'Paiement supprimé');
    }
}
