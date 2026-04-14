<?php

namespace Clair\Ai\ChatAi\LLM\Gemini;

use Clair\Ai\ChatAi\LLM\Gemini\GeminiResponseChoice;
use Clair\Ai\ChatAi\LLM\LLMResult;
use Clair\Ai\ChatAi\LLM\LLMResultChoice;
use Clair\Ai\ChatAi\LLM\StopReason;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Tool\Tool;
use DateTime;
use OpenAI\Responses\Chat\CreateResponse;

class GeminiResult implements LLMResult
{

    /**
     * @var GeminiResponseChoice[]
     */
    public readonly String $model;
    public readonly String $created;
    public readonly Array $choices;
    public readonly Int $completion_tokens;
    public readonly Int $prompt_tokens;
    public readonly Int $total_tokens;

    public function __construct(
        public readonly \stdClass $response,
        array|null $tools
    ) {
        $this->model = $response->model ?? null;
        $this->created = $response->created ?? null;
        $this->choices = $response->choices ?? [];

        $this->completion_tokens = $response->usage?->completion_tokens;
        $this->prompt_tokens     = $response->usage?->prompt_tokens;
        $this->total_tokens      = $response->usage?->total_tokens;
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    /**
     * AIの生成メッセージを配列(選択肢のため)で返す
     * @return LLMResultChoice[]
     */
    public function getChoices() :array
    {
        return array_map(function($v) :LLMResultChoice {
            $stop_reason = match ($v->finish_reason) {
                "stop" => StopReason::End,
                "length" => StopReason::MaxTokens,
                "content_filter" => StopReason::Filter,
                "safety" => StopReason::Safety,
                "tool_calls" => StopReason::ToolCall,
                "content_filter" => StopReason::ContentFilter,
                "incomplete" => StopReason::Uncompleted,
                null => StopReason::Uncompleted
            };
            $message = $v->message;
            $content = is_array($message)
                ? ($message['content'] ?? '')
                : ($message->content ?? '');

            return new LLMResultChoice(
                new AIMessage($content),
                $stop_reason
            );
            //return new LLMResultChoice(new AIMessage($v->message), $stop_reason);
        }, $this->choices);
    }

    public function getCreatedAt(): DateTime
    {
        $date_time = new DateTime();
        return $date_time->setTimestamp($this->created);
    }

    public function getUsage(): array
    {
        return ["input_tokens" => $this->prompt_tokens, "output_tokens" => $this->completion_tokens];
    }
}