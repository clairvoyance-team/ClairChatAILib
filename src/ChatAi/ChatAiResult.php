<?php
namespace Clair\Ai\ChatAi;

use Clair\Ai\ChatAi\ChatHistory\ChatHistory;
use Clair\Ai\ChatAi\LLM\LLMResultChoice;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\Message;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use DateTime;

class ChatAiResult
{

    /**
     * @param string $model_name
     * @param LLMResultChoice[] $choices
     * @param ChatPromptValue $prompt_value LLMに実際に送ったメッセージリスト
     * @param DateTime $created_at
     * @param int $input_token
     * @param int $output_token
     */
    public function __construct(
        public readonly string $model_name,
        public readonly array $choices,
        public readonly ChatPromptValue $prompt_value,
        public readonly DateTime $created_at,
        public readonly int $input_token,
        public readonly int $output_token
    )
    {}

    /**
     * @return string|null
     */
    public function getContents() :?string
    {
        return $this->choices[0]->getContents();
    }

    /**
     * @return ToolCallingContent[]|null
     */
    public function getTools() :?array
    {
        return $this->choices[0]->getTools();
    }

    /**
     * [tool_call_id、結果]の組み合わせ　のリストを返す
     * @return array{tool_call_id: int, result: mixed}|null
     */
    public function runTools() :?array
    {
        return $this->choices[0]->runTools();
    }

    /**
     * @return Message[]
     */
    public function getSentMessages(): array
    {
        return $this->prompt_value->messages;
    }

    public function getChoiceMessage(int $index) :AIMessage
    {
        return $this->choices[$index]->message;
    }
}