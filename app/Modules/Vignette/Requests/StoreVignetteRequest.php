<?php

namespace App\Modules\Vignette\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVignetteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vehicle_id'        => 'required|uuid|exists:vehicles,id',
            'year'              => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'issue_date'        => 'required|date',
            'expiry_date'       => 'required|date|after:issue_date',
            'amount'            => 'required|numeric|min:0',
            'payment_method'    => 'nullable|in:cash,bank_transfer,online',
            'payment_reference' => 'nullable|string|max:255',
            'is_paid'           => 'nullable|boolean',
        ];
    }
}

