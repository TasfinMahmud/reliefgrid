<?php

namespace App\Http\Requests\Stock;

use App\Models\StockTransfer;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockTransfer::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'stock_batch_id' => ['required', 'integer', 'exists:stock_batches,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
