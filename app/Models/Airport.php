<?php

namespace App\Models;

use Database\Factories\AirportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['iata_code', 'name', 'city', 'city_code', 'country_code', 'region_code', 'latitude', 'longitude', 'timezone'])]
class Airport extends Model
{
    /** @use HasFactory<AirportFactory> */
    use HasFactory;

    public function departingFlights(): HasMany
    {
        return $this->hasMany(Flight::class, 'airport_departure_id');
    }

    public function arrivingFlights(): HasMany
    {
        return $this->hasMany(Flight::class, 'airport_arrival_id');
    }
}
