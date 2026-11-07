<?php

namespace App\Http\Requests\Shelter;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:1000']];
    }
}
