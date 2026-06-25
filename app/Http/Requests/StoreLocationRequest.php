<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['organizational_unit_id' => ['required', 'string', 'size:26'], 'parent_id' => ['nullable', 'string', 'size:26'], 'type' => ['required', 'in:building,block,floor,room,warehouse,laboratory,external_area,other'], 'code' => ['nullable', 'string', 'max:64'], 'name' => ['required', 'string', 'max:255'], 'status' => ['required', 'in:active,inactive']];
    }
}
