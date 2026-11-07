<?php

namespace App\Http\Requests\Shelter;

use App\Models\ShelterRepairRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ShelterRepairRequest::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'household_id' => ['required', 'integer', 'exists:households,id'],
            'damage_type' => ['required', Rule::in(array_keys(ShelterRepairRequest::DAMAGE_TYPES))],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['description.min' => 'Please describe the damage in at least 10 characters.'];
    }
}
