<?php

namespace App\Modules\Parameter\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParameterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'category'   => 'sometimes|in:insurance_type,insurance_company,inspection_center,expense_category,accident_type,maintenance_type,maintenance_sub_type',
            'value'      => [
                'sometimes', 'string', 'max:100',
                Rule::unique('parameters', 'value')
                    ->where('category', $this->input('category'))
                    ->ignore($this->route('id')),
            ],
            'label'      => 'sometimes|string|max:150',
            'is_active'  => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
