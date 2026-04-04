<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAirportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $airportId = $this->route('airport')?->id;

        return [
            'iata_code' => ['sometimes', 'string', 'size:3', Rule::unique('airports', 'iata_code')->ignore($airportId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'city_code' => ['sometimes', 'string', 'max:3'],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'region_code' => ['nullable', 'string', 'max:10'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'timezone' => ['sometimes', 'string', 'max:100', 'timezone:all'],
        ];
    }
}
