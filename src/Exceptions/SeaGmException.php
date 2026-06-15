<?php

namespace Rrq\Seagm\Exceptions;

use Exception;

class SeaGmException extends Exception
{
    protected ?array $responseBody;
    protected array $requestParams;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?array $responseBody = null,
        array $requestParams = []
    ) {
        parent::__construct($message, $code);
        $this->responseBody  = $responseBody;
        $this->requestParams = $requestParams;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    public function getRequestParams(): array
    {
        return $this->requestParams;
    }
}
