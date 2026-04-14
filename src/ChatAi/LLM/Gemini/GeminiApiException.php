<?php

namespace Clair\Ai\ChatAi\LLM\Gemini;

class GeminiApiException extends \Exception
{
    private $responseBody;
    private $statusCode;

    public function __construct($message, $statusCode = 0, $responseBody = null, \Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getResponseBody()
    {
        return $this->responseBody;
    }
}

