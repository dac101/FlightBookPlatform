<?php

namespace App\Enums;

enum TripStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            TripStatus::Pending => 'Pending',
            TripStatus::Confirmed => 'Confirmed',
            TripStatus::Cancelled => 'Cancelled',
        };
    }
}
