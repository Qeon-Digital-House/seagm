<?php

namespace Rrq\Seagm\Enums;

enum PayStatus: int
{
    case UNPAID = 1;
    case PAID   = 2;

    public function label(): string
    {
        return match($this) {
            self::UNPAID => 'Unpaid',
            self::PAID   => 'Paid',
        };
    }
}
