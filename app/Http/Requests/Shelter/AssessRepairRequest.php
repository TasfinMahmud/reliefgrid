<?php

namespace App\Http\Requests\Shelter;

use App\Models\ShelterRepairRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role + state checked by ShelterRepairRequestPolicy@assess in the controller
    }

    public function rules(): array
    {
        return [
            'severity' => ['required', Rule::in(array_keys(ShelterRepairRequest::SEVERITIES))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'estimated_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'materials' => ['nullable', 'array', 'max:30'],
            'materials.*.item_name' => ['required', 'string', 'max:120'],
            'materials.*.unit' => ['required', 'string', 'max:30'],
            'materials.*.quantity_required' => ['required', 'numeric', 'gt:0', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'materials.*.item_name' => 'material name',
            'materials.*.unit' => 'material unit',
            'materials.*.quantity_required' => 'material quantity',
        ];
    }
}
