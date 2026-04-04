<?php

namespace App\Models;

use Database\Factories\AirlineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'iata_code'])]
class Airline extends Model
{
    /** @use HasFactory<AirlineFactory> */
    use HasFactory;

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }
}
