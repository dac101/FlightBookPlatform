<?php

namespace App\Enums;

enum SegmentType: string
{
    case Outbound = 'outbound';
    case Return = 'return';
    case Connection = 'connection';

    public function label(): string
    {
        return match ($this) {
            SegmentType::Outbound => 'Outbound',
            SegmentType::Return => 'Return',
            SegmentType::Connection => 'Connection',
        };
    }
}
