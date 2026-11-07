<?php

namespace App\Http\Requests\Household;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('household'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'block_id' => ['required', 'integer', 'exists:blocks,id'],
            'head_name' => ['required', 'string', 'max:120'],
            'head_sex' => ['nullable', Rule::in(['female', 'male', 'other'])],
            'head_age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]+$/'],
            'household_size' => ['required', 'integer', 'min:1', 'max:50'],
            'shelter_number' => ['nullable', 'string', 'max:40'],
            'vulnerability_notes' => ['nullable', 'string', 'max:1000'],
            'consent_given' => ['nullable', 'boolean'],
        ];
    }
}
