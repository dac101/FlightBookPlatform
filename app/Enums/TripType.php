<?php

namespace App\Enums;

enum TripType: string
{
    case OneWay = 'one_way';
    case RoundTrip = 'round_trip';
    case OpenJaw = 'open_jaw';
    case MultiCity = 'multi_city';

    public function label(): string
    {
        return match ($this) {
            TripType::OneWay => 'One Way',
            TripType::RoundTrip => 'Round Trip',
            TripType::OpenJaw => 'Open Jaw',
            TripType::MultiCity => 'Multi City',
        };
    }
}
