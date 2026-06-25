<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCustomFieldDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['asset_category_id' => ['required', 'string', 'size:26'], 'name' => ['required', 'string', 'max:255'], 'key' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'], 'field_type' => ['required', Rule::in(['text', 'textarea', 'integer', 'decimal', 'date', 'boolean', 'select'])], 'is_required' => ['nullable', 'boolean'], 'options' => ['nullable', 'array'], 'options.*' => ['string', 'max:255'], 'validation_rules' => ['nullable', 'array'], 'validation_rules.*' => ['string', Rule::in(['nullable', 'required', 'string', 'integer', 'numeric', 'date', 'boolean', 'max:255', 'max:1000'])], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'], 'is_active' => ['nullable', 'boolean']];
    }
}
