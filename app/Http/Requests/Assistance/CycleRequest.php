<?php

namespace App\Http\Requests\Assistance;

use App\Models\AssistanceCycle;
use Illuminate\Foundation\Http\FormRequest;

class CycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cycle = $this->route('cycle');

        return $cycle instanceof AssistanceCycle
            ? $this->user()->can('update', $cycle)
            : $this->user()->can('create', AssistanceCycle::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'block_ids' => ['nullable', 'array'],
            'block_ids.*' => ['integer', 'exists:blocks,id'],
        ];
    }
}
