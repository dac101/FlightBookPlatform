<?php

namespace App\Models;

use App\Enums\SegmentType;
use Database\Factories\TripSegmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trip_id', 'flight_id', 'segment_order', 'departure_date', 'segment_type'])]
class TripSegment extends Model
{
    /** @use HasFactory<TripSegmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'segment_type' => SegmentType::class,
            'segment_order' => 'integer',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }
}
