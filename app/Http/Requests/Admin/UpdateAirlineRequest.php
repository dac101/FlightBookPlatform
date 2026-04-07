<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAirlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $airline = $this->route('airline');
        $airlineId = is_object($airline) ? $airline->id : $airline;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'iata_code' => ['sometimes', 'string', 'max:3', Rule::unique('airlines', 'iata_code')->ignore($airlineId)],
        ];
    }
}
