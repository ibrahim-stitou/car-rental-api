<?php

namespace App\Modules\Vehicle\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'agency_id'           => 'required|uuid|exists:agencies,id',
            'brand'               => 'required|string|max:50',
            'model'               => 'required|string|max:50',
            'year'                => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'registration_number' => 'required|string|unique:vehicles,registration_number',
            'vin'                 => 'required|string|size:17|unique:vehicles,vin',
            'color'               => 'nullable|string|max:30',
            'category'            => 'required|in:sedan,suv,van,truck,convertible,coupe,hatchback,minivan',
            'fuel_type'           => 'required|in:gasoline,diesel,electric,hybrid',
            'transmission'        => 'required|in:manual,automatic',
            'seats'               => 'required|integer|min:2|max:9',
            'daily_rate'          => 'required|numeric|min:0',
            'hourly_rate'         => 'nullable|numeric|min:0',
            'monthly_rate'        => 'nullable|numeric|min:0',
            'deposit_amount'      => 'required|numeric|min:0',
            'mileage'             => 'required|integer|min:0',
            'average_consumption' => 'nullable|numeric|min:0|max:99.99',
            'status'                   => 'nullable|in:available,rented,maintenance,out_of_service',
            'condition'                => 'nullable|in:bon_etat,leger_dommage,accidente,hors_service',
            'description'              => 'nullable|string',
            'notes'                    => 'nullable|string',
            'show_on_website'          => 'nullable|boolean',
            'website_description'      => 'nullable|string',
            'website_price_override'   => 'nullable|numeric|min:0',
        ];
    }
}

