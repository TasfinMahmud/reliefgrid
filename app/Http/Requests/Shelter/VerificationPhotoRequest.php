<?php

namespace App\Http\Requests\Shelter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerificationPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:8192'],
            'caption' => ['nullable', 'string', 'max:200'],
            // Filled by the browser geolocation helper when the photo has no EXIF GPS block.
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'geo_source' => ['nullable', Rule::in(['device'])],
        ];
    }
}
