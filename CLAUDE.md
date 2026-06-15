# SeaGM Laravel Package

## Project Overview

Laravel package for SeaGM Open API integration (`rrq/seagm`). Supports product catalog browsing, direct top-up order creation, order status checking, and account balance retrieval.

Authentication uses HMAC SHA256 signature — every request automatically injects `uid`, `timestamp`, and `signature` as query parameters. All API responses are unwrapped from the `{code, msg, data}` envelope; methods return the `data` payload directly or throw `SeaGmException` on non-200 codes.

**Framework:** PHP 8.1+, Laravel 10/11 compatible  
**API Docs:** https://doc.openapi.seagm.io

---

## Directory Structure

```
seagm/
├── config/
│   └── seagm.php               # Package config (account_id, secret_key, base_url, etc.)
├── src/
│   ├── Enums/
│   │   ├── OrderStatus.php     # status_code: WAIT_SEND, SENDING, DONE, FAILED, REFUNDED
│   │   ├── PayStatus.php       # pay_status_code: UNPAID, PAID
│   │   └── SendStatus.php      # send_status_code: WAIT_SEND, SENDING, DONE, FAILED
│   ├── Exceptions/
│   │   └── SeaGmException.php  # Carries response body and request params
│   ├── Facades/
│   │   └── SeaGmFacade.php     # Laravel facade alias: SeaGm::
│   ├── SeaGm.php               # Main client class
│   └── SeaGmServiceProvider.php
├── tests/
│   └── Unit/
│       ├── Enums/
│       │   ├── OrderStatusTest.php
│       │   ├── PayStatusTest.php
│       │   └── SendStatusTest.php
│       └── SeaGmTest.php
├── composer.json
├── phpunit.xml
└── CLAUDE.md
```

---

## Base URLs

| Environment | URL |
|-------------|-----|
| Production  | `https://openapi.seagm.com` |
| Sandbox     | `https://openapi.seagm.io` |

Controlled via `SEAGM_IS_PRODUCTION` env var. Set to `false` to use sandbox automatically, or override with `SEAGM_BASE_URL`.

---

## Running Tests

PHP and Composer are not available on the host. Tests must be run inside the `teamrrq-infra-rrq-topup-1` Docker container.

```bash
# First time — copy source into container and install dependencies
docker cp /home/shinomiya/www/seagm teamrrq-infra-rrq-topup-1:/tmp/seagm
docker exec teamrrq-infra-rrq-topup-1 composer install --no-interaction -d /tmp/seagm

# Run tests
docker exec teamrrq-infra-rrq-topup-1 /tmp/seagm/vendor/bin/phpunit --configuration /tmp/seagm/phpunit.xml

# After code changes — sync and re-run
docker cp /home/shinomiya/www/seagm/src teamrrq-infra-rrq-topup-1:/tmp/seagm/
docker cp /home/shinomiya/www/seagm/tests teamrrq-infra-rrq-topup-1:/tmp/seagm/
docker exec teamrrq-infra-rrq-topup-1 /tmp/seagm/vendor/bin/phpunit --configuration /tmp/seagm/phpunit.xml
```

Expected output: `25 tests, 60 assertions`

---

## Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `SEAGM_ACCOUNT_ID` | Yes | — | Account ID (`uid`) used in every request |
| `SEAGM_SECRET_KEY` | Yes | — | Secret key for HMAC SHA256 signature |
| `SEAGM_IS_PRODUCTION` | No | `true` | Switch between production and sandbox |
| `SEAGM_BASE_URL` | No | auto | Override base URL (takes precedence over `SEAGM_IS_PRODUCTION`) |
| `SEAGM_TIMEOUT` | No | `30` | HTTP request timeout in seconds |
| `SEAGM_CALLBACK_URL` | No | — | Webhook URL for order status callbacks |
| `SEAGM_CALLBACK_TOKEN` | No | — | Token for validating incoming callback requests |

Add to your Laravel `.env`:

```env
SEAGM_ACCOUNT_ID=your_account_id
SEAGM_SECRET_KEY=your_secret_key
SEAGM_IS_PRODUCTION=false
```

Publish config file:

```bash
php artisan vendor:publish --tag=seagm-config
```
