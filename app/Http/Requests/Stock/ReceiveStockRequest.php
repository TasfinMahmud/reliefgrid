<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('operate', $this->route('warehouse'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'batch_number' => ['required', 'string', 'max:60'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'received_at' => ['required', 'date', 'before_or_equal:today'],
            'supplier' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return [
            'expiry_date.after' => 'Stock that has already expired cannot be received.',
            'received_at.before_or_equal' => 'The receipt date cannot be in the future.',
        ];
    }
}
