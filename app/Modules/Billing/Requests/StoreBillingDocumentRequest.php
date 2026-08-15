<?php

namespace App\Modules\Billing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillingDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $rules = [
            'type'                     => 'required|in:BC,BR,BL,DV,FA,AV,LLD',
            'agency_id'                => 'required|uuid|exists:agencies,id',
            // LLD documents must be linked to their long-term contract — this
            // is what lets the invoice pull its months-due/monthly-rate data
            // from a single source of truth (the reservation) instead of
            // carrying its own, separately-editable figures.
            'reservation_id'           => $this->input('type') === 'LLD'
                ? ['required', 'uuid', Rule::exists('reservations', 'id')->where('rental_unit', 'month')]
                : 'nullable|uuid|exists:reservations,id',
            'client_id'                => 'nullable|uuid|exists:clients,id',
            'client_name'              => 'required|string|max:255',
            'client_ice'               => 'nullable|string|max:30',
            'client_address'           => 'nullable|string',
            'client_phone'             => 'nullable|string|max:20',
            'client_email'             => 'nullable|email',
            'issue_date'               => 'required|date',
            'due_date'                 => 'nullable|date|after:issue_date',
            'delivery_date'            => 'nullable|date',
            'tax_rate'                 => 'nullable|numeric|min:0|max:100',
            'discount_percentage'      => 'nullable|numeric|min:0|max:100',
            'notes'                    => 'nullable|string',
            'terms_conditions'         => 'nullable|string',
            'reference_document_number'=> 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.description'      => 'required|string',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit'             => 'nullable|string|max:50',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.tax_rate'         => 'nullable|numeric|min:0|max:100',
            'items.*.notes'            => 'nullable|string',
        ];

        return $rules;
    }
}