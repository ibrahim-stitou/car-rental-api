<?php

namespace App\Modules\Reservation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'agency_id'           => 'required|uuid|exists:agencies,id',
            'vehicle_id'          => 'required|uuid|exists:vehicles,id',
            'client_id'           => 'required|uuid|exists:clients,id',
            'pickup_date'         => 'required|date|after_or_equal:today',
            'return_date'         => 'required|date|after:pickup_date',
            'pickup_location'     => 'required|string|max:255',
            'return_location'     => 'required|string|max:255',
            'daily_rate'          => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'additional_fees'     => 'nullable|numeric|min:0',
            'deposit_amount'      => 'nullable|numeric|min:0',
            'payment_method'      => 'nullable|in:cash,card,bank_transfer,online',
            'fuel_level_pickup'   => 'nullable|in:empty,quarter,half,three_quarters,full',
            'initial_mileage'     => 'nullable|integer|min:0',
            'notes'               => 'nullable|string',
        ];
    }
}

