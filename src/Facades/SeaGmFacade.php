<?php

namespace Rrq\Seagm\Facades;

use Illuminate\Support\Facades\Facade;
use Rrq\Seagm\SeaGm;

/**
 * @method static array getProductCategories()
 * @method static array getProducts(string $categoryId)
 * @method static array getProductItems(string $typeId)
 * @method static array createOrder(int $typeId, array $fields = [], int $buyAmount = 1, string $mchOrderId = '')
 * @method static array getOrderStatus(int $orderId, string $queryType = 'orderId')
 * @method static array getBalance()
 *
 * @see SeaGm
 */
class SeaGmFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SeaGm::class;
    }
}
