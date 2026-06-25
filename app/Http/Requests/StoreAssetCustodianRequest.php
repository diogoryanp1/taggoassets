<?php

namespace App\Http\Requests;

use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCustodianRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'organizational_unit' => ['required', 'string', 'size:26'],
            'user' => ['nullable', 'string', 'size:26'],
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:64', Rule::unique('asset_custodians', 'registration_number')->where('tenant_id', app(CurrentTenant::class)->id())->ignore($this->route('custodian'))],
            'document_identifier' => ['nullable', 'string', 'max:128'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'position' => ['nullable', 'string', 'max:255'],
        ];
    }
}
