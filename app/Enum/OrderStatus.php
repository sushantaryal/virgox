<?php

namespace App\Enum;

enum OrderStatus: int
{
    CASE OPEN = 1;
    CASE FILLED = 2;
    CASE CANCELLED = 3;

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::FILLED => 'Filled',
            self::CANCELLED => 'Cancelled',
        };
    }
}
