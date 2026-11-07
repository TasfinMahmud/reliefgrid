<?php

namespace App\Http\Requests\Shelter;

use Illuminate\Foundation\Http\FormRequest;

class IssueMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'stock_batch_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
