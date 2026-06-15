# SeaGM Laravel Package

Laravel package for [SeaGM Open API](https://doc.openapi.seagm.io) integration. Supports product catalog browsing, order creation, order status checking, and account balance retrieval.

---

## Requirements

- PHP 8.1+
- Laravel 10 or 11
- Guzzle 7.x

---

## Installation

```bash
composer require rrq/seagm
```

Laravel will auto-discover the service provider. To publish the config file:

```bash
php artisan vendor:publish --tag=seagm-config
```

---

## Configuration

Add the following to your `.env`:

```env
SEAGM_ACCOUNT_ID=your_account_id
SEAGM_SECRET_KEY=your_secret_key
SEAGM_IS_PRODUCTION=false
```

| Variable | Required | Default | Description |
|---|---|---|---|
| `SEAGM_ACCOUNT_ID` | Yes | — | Account ID (`uid`) sent with every request |
| `SEAGM_SECRET_KEY` | Yes | — | Secret key for HMAC SHA256 signature |
| `SEAGM_IS_PRODUCTION` | No | `true` | `true` = production, `false` = sandbox |
| `SEAGM_BASE_URL` | No | auto | Override base URL (takes precedence over `SEAGM_IS_PRODUCTION`) |
| `SEAGM_TIMEOUT` | No | `30` | HTTP request timeout in seconds |
| `SEAGM_CALLBACK_URL` | No | — | Webhook URL for order status callbacks |
| `SEAGM_CALLBACK_TOKEN` | No | — | Token for validating incoming callback requests |

| Environment | Base URL |
|---|---|
| Production | `https://openapi.seagm.com` |
| Sandbox | `https://openapi.seagm.io` |

---

## Enums

### `OrderStatus`

Represents the overall order status (`status_code`).

| Case | Value | Label |
|---|---|---|
| `OrderStatus::WAIT_SEND` | `10001` | Wait Send |
| `OrderStatus::SENDING` | `10002` | Sending |
| `OrderStatus::DONE` | `10003` | Done |
| `OrderStatus::FAILED` | `10004` | Failed |
| `OrderStatus::REFUNDED` | `10005` | Refunded |

```php
use Rrq\Seagm\Enums\OrderStatus;

$status = OrderStatus::from($order['status_code']);

$status->label();        // "Done"
$status->isPending();    // true if WAIT_SEND or SENDING
$status->isTerminal();   // true if DONE, FAILED, or REFUNDED
```

### `PayStatus`

Represents the payment status (`pay_status_code`).

| Case | Value | Label |
|---|---|---|
| `PayStatus::UNPAID` | `1` | Unpaid |
| `PayStatus::PAID` | `2` | Paid |

```php
use Rrq\Seagm\Enums\PayStatus;

$status = PayStatus::from($order['pay_status_code']);
$status->label(); // "Paid"
```

### `SendStatus`

Represents the delivery status (`send_status_code`).

| Case | Value | Label |
|---|---|---|
| `SendStatus::WAIT_SEND` | `1` | Wait Send |
| `SendStatus::SENDING` | `2` | Sending |
| `SendStatus::DONE` | `3` | Done |
| `SendStatus::FAILED` | `4` | Failed |

```php
use Rrq\Seagm\Enums\SendStatus;

$status = SendStatus::from($order['send_status_code']);
$status->label(); // "Sending"
```

---

## Usage

All methods are available via the `SeaGm` facade or dependency injection. On non-200 responses a `SeaGmException` is thrown.

```php
use Rrq\Seagm\Facades\SeaGmFacade as SeaGm;
use Rrq\Seagm\Exceptions\SeaGmException;
```

### `getProductCategories()`

Retrieve all available recharge categories.

```php
$categories = SeaGm::getProductCategories();
```

**Returns** — array of category objects, for example:

```json
[
    {
        "id": "mobile-legends",
        "name": "Mobile Legends",
        "icon_url": "https://..."
    }
]
```

---

### `getProducts(string $categoryId)`

Retrieve all recharge types (products) under a category.

| Parameter | Type | Description |
|---|---|---|
| `$categoryId` | `string` | Category ID from `getProductCategories()` |

```php
$products = SeaGm::getProducts('mobile-legends');
```

**Returns** — array of product/type objects, for example:

```json
[
    {
        "id": "ml-diamonds",
        "name": "Mobile Legends Diamonds",
        "category_id": "mobile-legends"
    }
]
```

---

### `getProductItems(string $typeId)`

Retrieve detail and available denominations for a specific recharge type.

| Parameter | Type | Description |
|---|---|---|
| `$typeId` | `string` | Type ID from `getProducts()` |

```php
$items = SeaGm::getProductItems('ml-diamonds');
```

**Returns** — product detail with item list, for example:

```json
{
    "id": "ml-diamonds",
    "name": "Mobile Legends Diamonds",
    "items": [
        {
            "type_id": 1001,
            "name": "86 Diamonds",
            "price": 15000
        }
    ]
}
```

---

### `createOrder(int $typeId, array $fields, int $buyAmount, string $mchOrderId)`

Create a new recharge order.

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$typeId` | `int` | — | Item type ID from `getProductItems()` |
| `$fields` | `array` | `[]` | Additional fields required by the product (e.g. `user_id`, `zone_id`) |
| `$buyAmount` | `int` | `1` | Quantity to purchase |
| `$mchOrderId` | `string` | `''` | Your own order reference ID (optional) |

```php
$order = SeaGm::createOrder(
    typeId: 1001,
    fields: ['user_id' => '123456789', 'zone_id' => '1234'],
    buyAmount: 1,
    mchOrderId: 'MY-ORDER-001'
);
```

**Returns** — created order detail, for example:

```json
{
    "order_id": 9876543,
    "mch_order_id": "MY-ORDER-001",
    "type_id": 1001,
    "buy_amount": 1,
    "status_code": 10001,
    "pay_status_code": 1,
    "send_status_code": 1
}
```

---

### `getOrderStatus(int $orderId, string $queryType)`

Check the status of an existing order.

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$orderId` | `int` | — | Order ID to query |
| `$queryType` | `string` | `'orderId'` | Query type: `'orderId'` or `'mchOrderId'` |

```php
// Query by SeaGM order ID
$status = SeaGm::getOrderStatus(9876543);

// Query by your own merchant order ID
$status = SeaGm::getOrderStatus('MY-ORDER-001', 'mchOrderId');
```

**Returns** — order detail with current status, for example:

```json
{
    "order_id": 9876543,
    "mch_order_id": "MY-ORDER-001",
    "status_code": 10003,
    "pay_status_code": 2,
    "send_status_code": 3
}
```

Use `OrderStatus`, `PayStatus`, and `SendStatus` enums to interpret the status codes:

```php
use Rrq\Seagm\Enums\OrderStatus;
use Rrq\Seagm\Enums\PayStatus;
use Rrq\Seagm\Enums\SendStatus;

$status = SeaGm::getOrderStatus(9876543);

$orderStatus = OrderStatus::from($status['status_code']);   // OrderStatus::DONE
$payStatus   = PayStatus::from($status['pay_status_code']); // PayStatus::PAID
$sendStatus  = SendStatus::from($status['send_status_code']); // SendStatus::DONE

$orderStatus->label();      // "Done"
$orderStatus->isTerminal(); // true
```

---

### `getBalance()`

Retrieve the current account balance.

```php
$balance = SeaGm::getBalance();
```

**Returns** — account balance info, for example:

```json
{
    "uid": "your_account_id",
    "balance": 500000,
    "currency": "IDR"
}
```

---

### `generateSignature(array $params)`

Generate an HMAC SHA256 signature for a given parameter set. Useful for verifying incoming callback requests against `SEAGM_CALLBACK_TOKEN`.

> **Note:** This method is `protected` on the `SeaGm` client and is called automatically on every request. It is exposed here for reference when implementing callback validation.

The signature is computed by:
1. Sorting all params by key (`ksort`)
2. Building a query string (`http_build_query`)
3. Hashing with HMAC SHA256 using your secret key

```php
// Manual callback validation example
$token     = config('seagm.callback_token');
$payload   = $request->all();
$signature = $request->header('X-Signature');

ksort($payload);
$expected = hash_hmac('sha256', http_build_query($payload), $token);

if (!hash_equals($expected, $signature)) {
    abort(401, 'Invalid signature');
}
```

---

## Order Callback

SeaGM sends an asynchronous POST request to your `SEAGM_CALLBACK_URL` when an order status changes.

### Requirements

- The callback URL must be publicly accessible.
- If you have an IP whitelist, contact SeaGM to add their server IPs.
- Your endpoint must respond with the plain string `success` to acknowledge receipt.

### Callback Payload

| Field | Type | Description |
|---|---|---|
| `id` | string | SeaGM order ID |
| `trade_id` | string | Payment/transaction ID |
| `title` | string | Order title |
| `category_id` | string | Product category ID |
| `product_id` | string | Product ID |
| `type_id` | string | Type/denomination ID |
| `created` | string | Order creation timestamp |
| `created_time` | string | Order creation time (Unix seconds) |
| `currency` | string | Transaction currency |
| `unit_price` | string | Per-unit price |
| `buy_amount` | string | Quantity purchased |
| `pay_amount` | string | Total payment amount |
| `pay_amount_credits` | string | Payment amount in credits |
| `refunded_amount` | string | Refunded amount (if applicable) |
| `refunded_reason` | string | Refund reason (if applicable) |
| `send_amount` | string | Quantity delivered |
| `paid_time` | string | Payment completion timestamp |
| `sent_time` | string | Delivery completion timestamp |
| `pay_status_code` | string | Payment status code — see `PayStatus` enum |
| `pay_status` | string | Payment status text |
| `send_status_code` | string | Delivery status code — see `SendStatus` enum |
| `send_status` | string | Delivery status text |
| `status_code` | string | Overall order status code — see `OrderStatus` enum |
| `status` | string | Overall order status text |
| `timestamp` | string | Callback timestamp |
| `mch_order_id` | string | Your merchant order reference ID |
| `signature` | string | HMAC SHA256 signature for verification |

### Example Payload

```json
{
    "id": "12563858",
    "trade_id": "11055829",
    "title": "HipVan S$30 HipVan",
    "category_id": "1006",
    "product_id": "6351",
    "type_id": "6351",
    "created": "1622444052",
    "created_time": "1622444052",
    "currency": "MYR",
    "unit_price": "1.09",
    "buy_amount": "1.00",
    "pay_amount": "1.09",
    "pay_amount_credits": "109",
    "refunded_amount": "0.00",
    "refunded_reason": "",
    "send_amount": "1.000",
    "paid_time": "1622444053",
    "sent_time": "1622444074",
    "pay_status_code": "2",
    "pay_status": "Paid",
    "send_status_code": "3",
    "send_status": "Done",
    "timestamp": 1622444077,
    "status": "Done",
    "status_code": 10003,
    "mch_order_id": "D2022031106",
    "signature": "fa79f96cf1bfce247cc592d7501a4c3d2d6014cf5ef1da0b75c5ab4535925c2e"
}
```

### Signature Verification

The `signature` field in the payload is computed by SeaGM using the same HMAC SHA256 algorithm as outgoing requests. Verify it using your `SEAGM_SECRET_KEY`:

1. Remove the `signature` field from the payload.
2. Sort remaining fields by key (`ksort`).
3. Build a query string (`http_build_query`).
4. Compute HMAC SHA256 with your secret key.
5. Compare with the received `signature` using a timing-safe comparison.

```php
use Illuminate\Http\Request;

public function handleCallback(Request $request)
{
    $payload   = $request->all();
    $signature = $payload['signature'] ?? '';

    unset($payload['signature']);
    ksort($payload);

    $expected = hash_hmac('sha256', http_build_query($payload), config('seagm.secret_key'));

    if (!hash_equals($expected, $signature)) {
        abort(401, 'Invalid signature');
    }

    // Process the order update
    $orderId     = $payload['id'];
    $mchOrderId  = $payload['mch_order_id'];
    $orderStatus = \Rrq\Seagm\Enums\OrderStatus::from((int) $payload['status_code']);

    // Must respond with plain "success" string
    return response('success', 200);
}
```

---

## Error Handling

```php
use Rrq\Seagm\Exceptions\SeaGmException;

try {
    $items = SeaGm::getProductItems('invalid-id');
} catch (SeaGmException $e) {
    $e->getMessage();       // Error message from API
    $e->getCode();          // API error code
    $e->getResponseBody();  // Full response body array
    $e->getRequestParams(); // Params that were sent
}
```
