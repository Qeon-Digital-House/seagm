<?php

namespace Rrq\Seagm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Rrq\Seagm\Exceptions\SeaGmException;

class SeaGm
{
    protected Client $client;
    protected string $accountId;
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct(array $config)
    {
        $this->accountId = $config['account_id'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->baseUrl   = rtrim($config['base_url'] ?? 'https://openapi.seagm.com', '/');

        $this->client = new Client([
            'base_uri' => $this->baseUrl . '/',
            'timeout'  => $config['timeout'] ?? 30,
            'headers'  => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    // ==================== PRODUCT METHODS ====================

    public function getProductCategories(): array
    {
        return $this->request('GET', 'v1/recharge-categories');
    }

    public function getProducts(string $categoryId): array
    {
        return $this->request('GET', "v1/recharge-categories/{$categoryId}/recharge-types");
    }

    public function getProductItems(string $typeId): array
    {
        return $this->request('GET', "v1/recharge-types/{$typeId}");
    }

    // ==================== ORDER METHODS ====================

    public function createOrder(int $typeId, array $fields = [], int $buyAmount = 1, string $mchOrderId = ''): array
    {
        $params = [
            'type_id'    => $typeId,
            'buy_amount' => $buyAmount,
        ];

        if ($mchOrderId !== '') {
            $params['mch_order_id'] = $mchOrderId;
        }

        return $this->request('POST', 'v1/recharge-orders', array_merge($params, $fields));
    }

    public function getOrderStatus(int $orderId, string $queryType = 'orderId'): array
    {
        return $this->request('GET', "v1/recharge-orders/{$orderId}", [
            'query_type' => $queryType,
        ]);
    }

    // ==================== BALANCE METHODS ====================

    public function getBalance(): array
    {
        return $this->request('GET', 'v1/me');
    }

    // ==================== CORE ====================

    protected function generateSignature(array $params): string
    {
        ksort($params);
        $queryString = http_build_query($params);
        return hash_hmac('sha256', $queryString, $this->secretKey);
    }

    protected function request(string $method, string $endpoint, array $params = []): array
    {
        $authParams = [
            'uid'       => $this->accountId,
            'timestamp' => time(),
        ];

        $allParams              = array_merge($authParams, $params);
        $authParams['signature'] = $this->generateSignature($allParams);

        try {
            $options = [];

            if (strtoupper($method) === 'GET') {
                $options[RequestOptions::QUERY] = array_merge($authParams, $params);
            } else {
                $options[RequestOptions::QUERY]       = $authParams;
                $options[RequestOptions::FORM_PARAMS] = $params;
            }

            $response = $this->client->request($method, $endpoint, $options);
            $body     = $response->getBody()->getContents();
            $decoded  = json_decode($body, true);

            if (!isset($decoded['code'])) {
                return $decoded ?? ['raw' => $body];
            }

            if ($decoded['code'] !== 200) {
                throw new SeaGmException(
                    $decoded['msg'] ?? 'Unknown error',
                    $decoded['code'],
                    $decoded,
                    $params
                );
            }

            return $decoded['data'];
        } catch (GuzzleException $e) {
            $statusCode   = $e->getCode();
            $responseBody = null;

            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $responseBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $message      = $responseBody['msg'] ?? $responseBody['message'] ?? $e->getMessage();
            } else {
                $message = $e->getMessage();
            }

            throw new SeaGmException($message, $statusCode, $responseBody, $params);
        }
    }
}
