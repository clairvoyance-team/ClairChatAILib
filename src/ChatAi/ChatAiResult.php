<?php
namespace Clair\Ai\ChatAi;

use Clair\Ai\ChatAi\ChatHistory\ChatHistory;
use Clair\Ai\ChatAi\LLM\LLMResultChoice;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use DateTime;

class ChatAiResult
{

    /**
     * @param string $model_name
     * @param LLMResultChoice[] $choices
     * @param ChatHistory $history 生成直前のチャット履歴
     * @param DateTime $created_at
     * @param int $input_token
     * @param int $output_token
     */
    public function __construct(
        public readonly string $model_name,
        public readonly array $choices,
        public readonly ChatHistory $history,
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
}