<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'symbol' => ['required', 'string', 'max:16'], 'type' => ['required', 'string', 'max:32', Rule::in(['unit', 'weight', 'length', 'area', 'volume', 'currency', 'time', 'other'])], 'decimal_places' => ['required', 'integer', 'min:0', 'max:6'], 'is_active' => ['nullable', 'boolean']];
    }
}
