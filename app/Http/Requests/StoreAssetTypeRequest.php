<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['asset_category_id' => ['required', 'string', 'size:26'], 'name' => ['required', 'string', 'max:255'], 'code' => ['nullable', 'string', 'max:64'], 'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['nullable', 'boolean'], 'requires_serial_number' => ['nullable', 'boolean'], 'requires_brand' => ['nullable', 'boolean'], 'requires_model' => ['nullable', 'boolean'], 'is_depreciable' => ['nullable', 'boolean'], 'default_useful_life_months' => ['nullable', 'integer', 'min:1', 'max:2400']];
    }
}
