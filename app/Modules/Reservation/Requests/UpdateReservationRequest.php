<?php

namespace App\Modules\Reservation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pickup_date'         => 'sometimes|date',
            'return_date'         => 'sometimes|date|after:pickup_date',
            'pickup_location'     => 'sometimes|string|max:255',
            'return_location'     => 'sometimes|string|max:255',
            'daily_rate'          => 'sometimes|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'additional_fees'     => 'nullable|numeric|min:0',
            'deposit_amount'      => 'nullable|numeric|min:0',
            'deposit_paid'        => 'nullable|boolean',
            'payment_method'      => 'nullable|in:cash,card,bank_transfer,online',
            'payment_status'      => 'nullable|in:pending,partial,paid,refunded',
            'fuel_level_pickup'   => 'nullable|in:empty,quarter,half,three_quarters,full',
            'fuel_level_return'   => 'nullable|in:empty,quarter,half,three_quarters,full',
            'initial_mileage'     => 'nullable|integer|min:0',
            'final_mileage'       => 'nullable|integer|min:0',
            'notes'               => 'nullable|string',
        ];
    }
}

