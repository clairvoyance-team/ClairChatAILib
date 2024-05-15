<?php
namespace Clair\Ai\ChatAi\LLM;

use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;

class LLMResultChoice
{

    /**
     * @param AIMessage $message
     * @param StopReason $stop_reason
     */
    public function __construct(
        public readonly AIMessage $message,
        public readonly StopReason $stop_reason
    )
    {}

    /**
     * @return string|null
     */
    public function getContents() :?string
    {
        return $this->message->getTextContents();
    }

    /**
     * @return ToolCallingContent[]|null
     */
    public function getTools() :?array
    {
        return $this->message->getToolCalling() ?: null;
    }

    /**
     * @return array{tool_call_id: int, result: mixed}|null
     */
    public function runTools() :?array
    {
        $tools_calling_contents = $this->message->getToolCalling();
        foreach ($tools_calling_contents as $tools_calling_content) {
            $result[] = [
                "tool_call_id" => $tools_calling_content->tool_call_id,
                "result" => $tools_calling_content->tool_call->run()
            ];
        }

        return $result ?? null;
    }
}