<?php

namespace Rrq\Seagm\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use Rrq\Seagm\Enums\PayStatus;

class PayStatusTest extends TestCase
{
    public function test_from_valid_codes(): void
    {
        $this->assertSame(PayStatus::UNPAID, PayStatus::from(1));
        $this->assertSame(PayStatus::PAID,   PayStatus::from(2));
    }

    public function test_try_from_invalid_code_returns_null(): void
    {
        $this->assertNull(PayStatus::tryFrom(99));
    }

    public function test_labels(): void
    {
        $this->assertSame('Unpaid', PayStatus::UNPAID->label());
        $this->assertSame('Paid',   PayStatus::PAID->label());
    }
}
