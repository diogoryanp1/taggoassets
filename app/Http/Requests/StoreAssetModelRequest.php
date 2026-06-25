<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['asset_brand_id' => ['required', 'string', 'size:26'], 'asset_type_id' => ['nullable', 'string', 'size:26'], 'name' => ['required', 'string', 'max:255'], 'manufacturer_code' => ['nullable', 'string', 'max:64'], 'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['nullable', 'boolean']];
    }
}
