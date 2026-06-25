<?php

namespace App\Http\Requests;

use App\Domain\Assets\Enums\AssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['asset_number' => ['nullable', 'string', 'max:64'], 'legacy_number' => ['nullable', 'string', 'max:64'], 'description' => ['required', 'string', 'max:255'], 'asset_category_id' => ['required', 'string', 'size:26'], 'asset_type_id' => ['required', 'string', 'size:26'], 'brand_id' => ['nullable', 'string', 'size:26'], 'model_id' => ['nullable', 'string', 'size:26'], 'unit_of_measure_id' => ['required', 'string', 'size:26'], 'condition_id' => ['required', 'string', 'size:26'], 'status' => ['nullable', Rule::enum(AssetStatus::class)], 'organizational_unit_id' => ['required', 'string', 'size:26'], 'location_id' => ['nullable', 'string', 'size:26'], 'acquisition_date' => ['nullable', 'date'], 'acquisition_value_cents' => ['nullable', 'integer', 'min:0'], 'serial_number' => ['nullable', 'string', 'max:128'], 'notes' => ['nullable', 'string', 'max:10000'], 'custom_values' => ['nullable', 'array']];
    }
}
