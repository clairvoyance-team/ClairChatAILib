<?php

namespace Clair\Ai\ChatAi\LLM\OpenAi;

use Clair\Ai\ChatAi\LLM\Exception\InvalidParameterException;
use Clair\Ai\ChatAi\LLM\Parameters;

class OpenAIChatCompletionParameters implements Parameters
{
    public readonly string $model;

    public readonly ?float $frequency_penalty;

    public readonly ?array $logit_bias;

    public readonly ?bool $logprobs;

    public readonly ?int $top_logprobs;

    public readonly ?int $max_tokens;

    public readonly ?int $n;

    public readonly ?float $presence_penalty;

    public readonly ?array $response_format;

    public readonly ?int $seed;

    public readonly null|string|array $stop;

    public readonly ?float $temperature;

    public readonly ?float $top_p;

    public readonly null|string|array $tool_choice;

    public readonly ?string $user;

    /**
     * @param array $params
     * @throws InvalidParameterException
     */
    public function __construct(array $params)
    {
        if (!isset($params["model"])) throw new InvalidParameterException("モデル名は必須です");

        //キー名がパラメータ名
        foreach ($params as $key => $value) {
            //{$key}という名前のパラメータが存在しない場合は例外
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                throw new InvalidParameterException("{$key}というパラメータは存在しません。");
            }
        }
    }

    public function toRequestArr(): array
    {
        $request = [];
        foreach ($this as $property_name => $value) {
            if ($property_name == "model") continue;
            $request[$property_name] = $value;
        }

        return $request;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}