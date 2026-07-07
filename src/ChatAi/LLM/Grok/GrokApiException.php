<?php

namespace Clair\Ai\ChatAi\LLM\Grok;

class GrokApiException extends \Exception
{
    private mixed $responseBody;
    private int $statusCode;

    public function __construct(string $message, int $statusCode = 0, mixed $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): mixed
    {
        return $this->responseBody;
    }
}

