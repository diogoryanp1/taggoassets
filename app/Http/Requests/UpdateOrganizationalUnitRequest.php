<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['parent_id' => ['nullable', 'string', 'size:26'], 'type' => ['required', 'string', 'max:64'], 'code' => ['nullable', 'string', 'max:64'], 'name' => ['required', 'string', 'max:255'], 'status' => ['required', 'in:active,inactive']];
    }
}
