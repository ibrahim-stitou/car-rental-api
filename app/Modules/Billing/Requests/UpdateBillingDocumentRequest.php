<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'                   => 'sometimes|in:draft,pending,approved,rejected,paid,cancelled',
            'client_name'              => 'sometimes|string|max:255',
            'client_ice'               => 'nullable|string|max:30',
            'client_address'           => 'nullable|string',
            'client_phone'             => 'nullable|string|max:20',
            'client_email'             => 'nullable|email',
            'due_date'                 => 'nullable|date',
            'delivery_date'            => 'nullable|date',
            'tax_rate'                 => 'nullable|numeric|min:0|max:100',
            'discount_percentage'      => 'nullable|numeric|min:0|max:100',
            'notes'                    => 'nullable|string',
            'terms_conditions'         => 'nullable|string',
            'items'                    => 'sometimes|array|min:1',
            'items.*.description'      => 'required_with:items|string',
            'items.*.quantity'         => 'required_with:items|integer|min:1',
            'items.*.unit_price'       => 'required_with:items|numeric|min:0',
            'items.*.tax_rate'         => 'nullable|numeric|min:0|max:100',
            'items.*.notes'            => 'nullable|string',
        ];
    }
}