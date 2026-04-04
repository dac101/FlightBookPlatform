<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'flight_number' => ['required', 'string', 'max:10'],
            'airline_id' => ['required', 'integer', 'exists:airlines,id'],
            'airport_departure_id' => ['required', 'integer', 'exists:airports,id', 'different:airport_arrival_id'],
            'airport_arrival_id' => ['required', 'integer', 'exists:airports,id'],
            'departure_time' => ['required', 'date_format:H:i'],
            'arrival_time' => ['required', 'date_format:H:i'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }
}
