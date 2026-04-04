<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'flight_number' => ['sometimes', 'string', 'max:10'],
            'airline_id' => ['sometimes', 'integer', 'exists:airlines,id'],
            'airport_departure_id' => ['sometimes', 'integer', 'exists:airports,id'],
            'airport_arrival_id' => ['sometimes', 'integer', 'exists:airports,id'],
            'departure_time' => ['sometimes', 'date_format:H:i'],
            'arrival_time' => ['sometimes', 'date_format:H:i'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }
}
