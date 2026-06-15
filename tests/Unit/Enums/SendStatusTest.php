<?php

namespace Rrq\Seagm\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use Rrq\Seagm\Enums\SendStatus;

class SendStatusTest extends TestCase
{
    public function test_from_valid_codes(): void
    {
        $this->assertSame(SendStatus::WAIT_SEND, SendStatus::from(1));
        $this->assertSame(SendStatus::SENDING,   SendStatus::from(2));
        $this->assertSame(SendStatus::DONE,      SendStatus::from(3));
        $this->assertSame(SendStatus::FAILED,    SendStatus::from(4));
    }

    public function test_try_from_invalid_code_returns_null(): void
    {
        $this->assertNull(SendStatus::tryFrom(99));
    }

    public function test_labels(): void
    {
        $this->assertSame('Wait Send', SendStatus::WAIT_SEND->label());
        $this->assertSame('Sending',   SendStatus::SENDING->label());
        $this->assertSame('Done',      SendStatus::DONE->label());
        $this->assertSame('Failed',    SendStatus::FAILED->label());
    }
}
