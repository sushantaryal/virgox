<?php

namespace App\Enum;

enum OrderStatus: int
{
    CASE OPEN = 1;
    CASE FILLED = 2;
    CASE CANCELLED = 3;
}
