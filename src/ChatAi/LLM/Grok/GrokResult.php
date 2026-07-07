<?php

namespace Clair\Ai\ChatAi\LLM\Grok;

use Clair\Ai\ChatAi\LLM\LLMResult;
use Clair\Ai\ChatAi\LLM\LLMResultChoice;
use Clair\Ai\ChatAi\LLM\StopReason;
use DateTime;

class GrokResult implements LLMResult
{
    /**
     * @var GrokResponseChoice[]
     */
    public readonly array $choices;

    /**
     * @param array|null $tools
     */
    public function __construct(
        public readonly \stdClass $response,
        ?array $tools
    ) {
        $this->choices = array_map(
            fn ($val) => GrokResponseChoice::fromStdClassChoice($val, $tools),
            $this->response->choices ?? []
        );
    }

    public function getModelName(): string
    {
        return (string) ($this->response->model ?? '');
    }

    /**
     * @return LLMResultChoice[]
     */
    public function getChoices(): array
    {
        return array_map(function (GrokResponseChoice $choice): LLMResultChoice {
            $stop_reason = match ($choice->finish_reason) {
                'stop' => StopReason::End,
                'length' => StopReason::MaxTokens,
                'content_filter' => StopReason::ContentFilter,
                'tool_calls' => StopReason::ToolCall,
                'safety' => StopReason::Safety,
                null => StopReason::Uncompleted,
                default => StopReason::Uncompleted,
            };

            return new LLMResultChoice($choice->message, $stop_reason);
        }, $this->choices);
    }

    public function getCreatedAt(): DateTime
    {
        $timestamp = $this->response->created ?? time();
        $date_time = new DateTime();
        return $date_time->setTimestamp((int) $timestamp);
    }

    public function getUsage(): array
    {
        $usage = $this->response->usage ?? new \stdClass();

        return [
            'input_tokens' => $usage->prompt_tokens ?? null,
            'output_tokens' => $usage->completion_tokens ?? null,
        ];
    }
}

