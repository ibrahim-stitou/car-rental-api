<?php

namespace App\Modules\Vehicle\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'agency_id'           => 'sometimes|uuid|exists:agencies,id',
            'brand'               => 'sometimes|string|max:50',
            'model'               => 'sometimes|string|max:50',
            'year'                => 'sometimes|integer|min:2000|max:' . (date('Y') + 1),
            'registration_number' => 'sometimes|string|unique:vehicles,registration_number,' . $this->route('id'),
            'vin'                 => 'sometimes|string|size:17|unique:vehicles,vin,' . $this->route('id'),
            'color'               => 'nullable|string|max:30',
            'category'            => 'sometimes|in:sedan,suv,van,truck,convertible,coupe,hatchback,minivan',
            'fuel_type'           => 'sometimes|in:gasoline,diesel,electric,hybrid',
            'transmission'        => 'sometimes|in:manual,automatic',
            'seats'               => 'sometimes|integer|min:2|max:9',
            'daily_rate'          => 'sometimes|numeric|min:0',
            'deposit_amount'      => 'sometimes|numeric|min:0',
            'mileage'             => 'sometimes|integer|min:0',
            'status'                   => 'nullable|in:available,rented,maintenance,out_of_service',
            'notes'                    => 'nullable|string',
            'show_on_website'          => 'nullable|boolean',
            'website_description'      => 'nullable|string',
            'website_price_override'   => 'nullable|numeric|min:0',
        ];
    }
}

