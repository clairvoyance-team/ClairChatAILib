<?php

namespace Clair\Ai\ChatAi\LLM\OpenAi;

use Clair\Ai\ChatAi\LLM\LLMResult;
use Clair\Ai\ChatAi\LLM\LLMResultChoice;
use Clair\Ai\ChatAi\LLM\StopReason;
use Clair\Ai\ChatAi\Tool\Tool;
use DateTime;
use OpenAI\Responses\Chat\CreateResponse;

class OpenAIResult implements LLMResult
{

    /**
     * @var OpenAIResponseChoice[]
     */
    public readonly array $choices;

    /**
     * @param CreateResponse $openai_response
     * @param Tool[] $tools
     */
    public function __construct(
        public readonly CreateResponse $openai_response,
        array|null $tools
    ) {

        $this->choices = array_map(function ($val) use ($tools) {
            return OpenAIResponseChoice::fromCreateResponseChoice($val, $tools);
        }, $this->openai_response->choices);

    }

    public function getModelName(): string
    {
        return $this->openai_response->model;
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
                "tool_calls" => StopReason::ToolCall,
                null => StopReason::Uncompleted
            };

            return new LLMResultChoice($v->message, $stop_reason);
        }, $this->choices);
    }

    public function getCreatedAt(): DateTime
    {
        $date_time = new DateTime();
        return $date_time->setTimestamp($this->openai_response->created);
    }

    public function getUsage(): array
    {
        return ["input_tokens" => $this->openai_response->usage->promptTokens, "output_tokens" => $this->openai_response->usage->completionTokens];
    }
}