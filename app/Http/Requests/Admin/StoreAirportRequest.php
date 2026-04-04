<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAirportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'iata_code' => ['required', 'string', 'size:3', 'unique:airports,iata_code'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'city_code' => ['required', 'string', 'max:3'],
            'country_code' => ['required', 'string', 'size:2'],
            'region_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'timezone' => ['required', 'string', 'max:100', 'timezone:all'],
        ];
    }
}
