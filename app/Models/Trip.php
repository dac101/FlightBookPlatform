<?php

namespace App\Models;

use App\Enums\TripStatus;
use App\Enums\TripType;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'trip_name', 'trip_type', 'status', 'departure_date', 'total_price_cache'])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'trip_type' => TripType::class,
            'status' => TripStatus::class,
            'departure_date' => 'date',
            'total_price_cache' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(TripSegment::class)->orderBy('segment_order');
    }

    public function totalPrice(): float
    {
        return (float) $this->segments
            ->load('flight')
            ->sum(fn (TripSegment $segment) => $segment->flight->price);
    }

    public function recalculateTotal(): void
    {
        $this->total_price_cache = $this->totalPrice();
        $this->save();
    }

    public function isValidDepartureWindow(): bool
    {
        $today = Carbon::today();
        $maxDate = Carbon::today()->addDays(365);
        $departure = Carbon::parse($this->departure_date);

        return $departure->greaterThanOrEqualTo($today)
            && $departure->lessThanOrEqualTo($maxDate);
    }
}
