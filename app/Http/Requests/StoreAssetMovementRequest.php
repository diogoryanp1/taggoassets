<?php

namespace App\Http\Requests;

use App\Domain\Assets\Enums\AssetMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetMovementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'asset' => ['required', 'string', 'size:26'],
            'movement_type' => ['required', Rule::enum(AssetMovementType::class)],
            'destination_organizational_unit' => ['nullable', 'string', 'size:26'],
            'destination_location' => ['nullable', 'string', 'size:26'],
            'destination_custodian' => ['nullable', 'string', 'size:26'],
            'related_movement' => ['nullable', 'string', 'size:26'],
            'expected_return_at' => ['nullable', 'date', 'after:now'],
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'origin_organizational_unit_id' => ['prohibited'],
            'origin_location_id' => ['prohibited'],
            'origin_custodian_id' => ['prohibited'],
            'requested_by' => ['prohibited'],
            'approved_by' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
