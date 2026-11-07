<?php

namespace App\Http\Requests\Assistance;

use Illuminate\Foundation\Http\FormRequest;

class EntitlementRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageRules', $this->route('cycle'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'min_household_size' => ['required', 'integer', 'min:1', 'max:50'],
            'max_household_size' => ['nullable', 'integer', 'min:1', 'max:50', 'gte:min_household_size'],
            'base_quantity' => ['required', 'numeric', 'min:0', 'max:100000'],
            'per_member_quantity' => ['required', 'numeric', 'min:0', 'max:100000'],
            'max_quantity' => ['nullable', 'numeric', 'min:0.01', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ((float) $this->input('base_quantity', 0) <= 0 && (float) $this->input('per_member_quantity', 0) <= 0) {
                $v->errors()->add('base_quantity', 'Either a base quantity or a per-member quantity must be greater than zero.');
            }
        });
    }
}
