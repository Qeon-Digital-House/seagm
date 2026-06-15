<?php

namespace Rrq\Seagm\Enums;

enum SendStatus: int
{
    case WAIT_SEND = 1;
    case SENDING   = 2;
    case DONE      = 3;
    case FAILED    = 4;

    public function label(): string
    {
        return match($this) {
            self::WAIT_SEND => 'Wait Send',
            self::SENDING   => 'Sending',
            self::DONE      => 'Done',
            self::FAILED    => 'Failed',
        };
    }
}
