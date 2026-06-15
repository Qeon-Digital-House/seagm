<?php

namespace Rrq\Seagm\Enums;

enum OrderStatus: int
{
    case WAIT_SEND = 10001;
    case SENDING   = 10002;
    case DONE      = 10003;
    case FAILED    = 10004;
    case REFUNDED  = 10005;

    public function label(): string
    {
        return match($this) {
            self::WAIT_SEND => 'Wait Send',
            self::SENDING   => 'Sending',
            self::DONE      => 'Done',
            self::FAILED    => 'Failed',
            self::REFUNDED  => 'Refunded',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [self::WAIT_SEND, self::SENDING]);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::DONE, self::FAILED, self::REFUNDED]);
    }
}
