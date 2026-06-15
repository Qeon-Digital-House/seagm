<?php

namespace Rrq\Seagm\Tests\Unit\Enums;

use PHPUnit\Framework\TestCase;
use Rrq\Seagm\Enums\OrderStatus;

class OrderStatusTest extends TestCase
{
    public function test_from_valid_codes(): void
    {
        $this->assertSame(OrderStatus::WAIT_SEND, OrderStatus::from(10001));
        $this->assertSame(OrderStatus::SENDING,   OrderStatus::from(10002));
        $this->assertSame(OrderStatus::DONE,      OrderStatus::from(10003));
        $this->assertSame(OrderStatus::FAILED,    OrderStatus::from(10004));
        $this->assertSame(OrderStatus::REFUNDED,  OrderStatus::from(10005));
    }

    public function test_try_from_invalid_code_returns_null(): void
    {
        $this->assertNull(OrderStatus::tryFrom(99999));
    }

    public function test_labels(): void
    {
        $this->assertSame('Wait Send', OrderStatus::WAIT_SEND->label());
        $this->assertSame('Sending',   OrderStatus::SENDING->label());
        $this->assertSame('Done',      OrderStatus::DONE->label());
        $this->assertSame('Failed',    OrderStatus::FAILED->label());
        $this->assertSame('Refunded',  OrderStatus::REFUNDED->label());
    }

    public function test_is_pending(): void
    {
        $this->assertTrue(OrderStatus::WAIT_SEND->isPending());
        $this->assertTrue(OrderStatus::SENDING->isPending());
        $this->assertFalse(OrderStatus::DONE->isPending());
        $this->assertFalse(OrderStatus::FAILED->isPending());
        $this->assertFalse(OrderStatus::REFUNDED->isPending());
    }

    public function test_is_terminal(): void
    {
        $this->assertTrue(OrderStatus::DONE->isTerminal());
        $this->assertTrue(OrderStatus::FAILED->isTerminal());
        $this->assertTrue(OrderStatus::REFUNDED->isTerminal());
        $this->assertFalse(OrderStatus::WAIT_SEND->isTerminal());
        $this->assertFalse(OrderStatus::SENDING->isTerminal());
    }
}
