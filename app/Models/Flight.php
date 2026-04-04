<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FlightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['flight_number', 'airline_id', 'airport_departure_id', 'airport_arrival_id', 'departure_time', 'arrival_time', 'price'])]
class Flight extends Model
{
    /** @use HasFactory<FlightFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class);
    }

    public function departureAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'airport_departure_id');
    }

    public function arrivalAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'airport_arrival_id');
    }

    public function tripSegments(): HasMany
    {
        return $this->hasMany(TripSegment::class);
    }

    public function durationMinutes(): int
    {
        $departure = CarbonImmutable::createFromFormat('H:i:s', $this->departure_time);
        $arrival = CarbonImmutable::createFromFormat('H:i:s', $this->arrival_time);

        if ($arrival->lessThanOrEqualTo($departure)) {
            $arrival = $arrival->addDay();
        }

        return $departure->diffInMinutes($arrival);
    }

    public function durationLabel(): string
    {
        $minutes = $this->durationMinutes();
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%dh %02dm', $hours, $remainingMinutes);
    }
}
