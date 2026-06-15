<?php

namespace Rrq\Seagm\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Rrq\Seagm\Exceptions\SeaGmException;
use Rrq\Seagm\SeaGm;

class SeaGmTest extends TestCase
{
    private SeaGm $seagm;
    private MockInterface $clientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientMock = Mockery::mock(Client::class);

        $this->seagm = new SeaGm([
            'account_id' => 'test-account',
            'secret_key' => 'test-secret',
            'base_url'   => 'https://openapi.seagm.io',
            'timeout'    => 30,
        ]);

        $reflection = new \ReflectionProperty(SeaGm::class, 'client');
        $reflection->setAccessible(true);
        $reflection->setValue($this->seagm, $this->clientMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== HELPERS ====================

    private function mockResponse(mixed $data, int $code = 200, string $msg = 'OK'): Response
    {
        $body = json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
        return new Response(200, ['Content-Type' => 'application/json'], $body);
    }

    private function mockHttpErrorResponse(int $httpStatus, string $msg): Response
    {
        return new Response($httpStatus, ['Content-Type' => 'application/json'], json_encode(['msg' => $msg]));
    }

    // ==================== PRODUCT METHODS ====================

    public function test_get_product_categories(): void
    {
        $data = [['id' => 1, 'name' => 'Mobile Games', 'code' => 'mobile']];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(fn($method, $endpoint) => $method === 'GET' && $endpoint === 'v1/recharge-categories')
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->getProductCategories();

        $this->assertSame($data, $result);
    }

    public function test_get_products(): void
    {
        $data = [['id' => 10, 'name' => 'Mobile Legends 100 Diamonds']];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(fn($method, $endpoint) => $method === 'GET' && $endpoint === 'v1/recharge-categories/1/recharge-types')
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->getProducts('1');

        $this->assertSame($data, $result);
    }

    public function test_get_product_items(): void
    {
        $data = ['id' => 10, 'name' => 'Mobile Legends 100 Diamonds', 'unit_price' => 1.50];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(fn($method, $endpoint) => $method === 'GET' && $endpoint === 'v1/recharge-types/10')
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->getProductItems('10');

        $this->assertSame($data, $result);
    }

    // ==================== ORDER METHODS ====================

    public function test_create_order_with_required_params_only(): void
    {
        $data = ['id' => 999, 'status_code' => 10001, 'pay_status_code' => 1];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(function ($method, $endpoint, $options) {
                return $method === 'POST'
                    && $endpoint === 'v1/recharge-orders'
                    && $options['form_params']['type_id'] === 100
                    && $options['form_params']['buy_amount'] === 1
                    && !isset($options['form_params']['mch_order_id']);
            })
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->createOrder(100);

        $this->assertSame($data, $result);
    }

    public function test_create_order_with_fields(): void
    {
        $data = ['id' => 999, 'status_code' => 10001, 'fields' => ['playerid' => '123456789']];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(function ($method, $endpoint, $options) {
                return $method === 'POST'
                    && $endpoint === 'v1/recharge-orders'
                    && $options['form_params']['type_id'] === 100
                    && $options['form_params']['playerid'] === '123456789'
                    && $options['form_params']['buy_amount'] === 1;
            })
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->createOrder(100, ['playerid' => '123456789']);

        $this->assertSame($data, $result);
    }

    public function test_create_order_with_all_params(): void
    {
        $data = ['id' => 999, 'status_code' => 10001];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(function ($method, $endpoint, $options) {
                return $method === 'POST'
                    && $endpoint === 'v1/recharge-orders'
                    && $options['form_params']['type_id'] === 100
                    && $options['form_params']['playerid'] === '123456789'
                    && $options['form_params']['buy_amount'] === 2
                    && $options['form_params']['mch_order_id'] === 'ORD-001';
            })
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->createOrder(100, ['playerid' => '123456789'], 2, 'ORD-001');

        $this->assertSame($data, $result);
    }

    public function test_get_order_status(): void
    {
        $data = ['id' => 999, 'status_code' => 10003, 'pay_status_code' => 2, 'send_status_code' => 3];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(fn($method, $endpoint) => $method === 'GET' && $endpoint === 'v1/recharge-orders/999')
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->getOrderStatus(999);

        $this->assertSame($data, $result);
    }

    public function test_get_order_status_by_mch_order_id(): void
    {
        $data = ['id' => 999, 'status_code' => 10003];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(function ($method, $endpoint, $options) {
                return $method === 'GET'
                    && $endpoint === 'v1/recharge-orders/999'
                    && $options['query']['query_type'] === 'mchOrderId';
            })
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->getOrderStatus(999, 'mchOrderId');

        $this->assertSame($data, $result);
    }

    // ==================== BALANCE ====================

    public function test_get_balance(): void
    {
        $data = ['id' => 123, 'username' => 'testuser', 'credits' => 5000, 'currency' => 'USD', 'balance' => '100.00'];

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(fn($method, $endpoint) => $method === 'GET' && $endpoint === 'v1/me')
            ->andReturn($this->mockResponse($data));

        $result = $this->seagm->getBalance();

        $this->assertSame($data, $result);
    }

    // ==================== SIGNATURE ====================

    public function test_signature_injected_in_get_request(): void
    {
        $capturedOptions = null;

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(function ($method, $endpoint, $options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->mockResponse([]));

        $this->seagm->getBalance();

        $this->assertArrayHasKey('uid', $capturedOptions['query']);
        $this->assertArrayHasKey('timestamp', $capturedOptions['query']);
        $this->assertArrayHasKey('signature', $capturedOptions['query']);
        $this->assertSame(64, strlen($capturedOptions['query']['signature']));
    }

    public function test_signature_injected_in_post_request(): void
    {
        $capturedOptions = null;

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->withArgs(function ($method, $endpoint, $options) use (&$capturedOptions) {
                $capturedOptions = $options;
                return true;
            })
            ->andReturn($this->mockResponse([]));

        $this->seagm->createOrder(100, ['playerid' => '123456789']);

        $this->assertArrayHasKey('uid', $capturedOptions['query']);
        $this->assertArrayHasKey('timestamp', $capturedOptions['query']);
        $this->assertArrayHasKey('signature', $capturedOptions['query']);
        $this->assertSame(64, strlen($capturedOptions['query']['signature']));
    }

    // ==================== ERROR HANDLING ====================

    public function test_throws_exception_on_api_level_error(): void
    {
        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->andReturn($this->mockResponse(null, 20033, 'Insufficient Balance'));

        $this->expectException(SeaGmException::class);
        $this->expectExceptionMessage('Insufficient Balance');
        $this->expectExceptionCode(20033);

        $this->seagm->createOrder(100);
    }

    public function test_throws_exception_on_http_error(): void
    {
        $errorResponse = $this->mockHttpErrorResponse(409, 'Invalid Signature');
        $exception     = new ClientException('Invalid Signature', new Request('GET', '/'), $errorResponse);

        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->andThrow($exception);

        $this->expectException(SeaGmException::class);
        $this->expectExceptionMessage('Invalid Signature');

        $this->seagm->getProductCategories();
    }

    public function test_seagm_exception_contains_response_body(): void
    {
        $this->clientMock
            ->shouldReceive('request')
            ->once()
            ->andReturn($this->mockResponse(null, 20033, 'Insufficient Balance'));

        try {
            $this->seagm->createOrder(100);
            $this->fail('Expected SeaGmException was not thrown');
        } catch (SeaGmException $e) {
            $this->assertSame(20033, $e->getCode());
            $this->assertSame('Insufficient Balance', $e->getMessage());
            $this->assertArrayHasKey('code', $e->getResponseBody());
        }
    }
}
