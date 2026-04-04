<?php

namespace App\Modules\Vignette\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVignetteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'        => 'sometimes|uuid|exists:vehicles,id',
            'year'              => 'sometimes|integer|min:2020|max:' . (date('Y') + 1),
            'issue_date'        => 'sometimes|date',
            'expiry_date'       => 'sometimes|date|after:issue_date',
            'amount'            => 'sometimes|numeric|min:0',
            'payment_method'    => 'nullable|in:cash,bank_transfer,online',
            'payment_reference' => 'nullable|string|max:255',
            'is_paid'           => 'nullable|boolean',
        ];
    }
}

