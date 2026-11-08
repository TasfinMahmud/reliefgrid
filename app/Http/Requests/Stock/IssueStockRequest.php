<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class IssueStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('operate', $this->route('warehouse'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'stock_batch_id' => ['required', 'integer', 'exists:stock_batches,id'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'assistance_cycle_id' => ['nullable', 'integer', 'exists:assistance_cycles,id'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string,string> */
    public function messages(): array
    {
        return ['note.required' => 'Record the purpose of this issue (e.g. distribution point and date).'];
    }
}
