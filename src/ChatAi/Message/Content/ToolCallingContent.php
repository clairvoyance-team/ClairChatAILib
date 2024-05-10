<?php


namespace Clair\Ai\ChatAi\Message\Content;

use Clair\Ai\ChatAi\Tool\ToolCall;
use Clair\Ai\ChatAi\Tool\ToolType;

class ToolCallingContent implements Content
{
    public function __construct(
        public readonly string   $tool_call_id,
        public readonly ToolType $tool_type,
        public readonly ToolCall $tool_call,
    )
    {
    }

    /**
     * @return array{tool_call_id: string, type: string, tool_name: string, tool_args: array}
     */
    public function getContents(): array
    {
        $function = $this->tool_call->toJsonArr();
        return [
            "tool_call_id" => $this->tool_call_id,
            "type" => $this->tool_type->value,
            "tool_name" => $function["name"],
            "tool_args" => $function["args"]
        ];
    }

    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function formatLog(): string
    {
        $output = "{$this->tool_type->value}:";
        $output .= $this->tool_call->logFormat();
        return $output;
    }
}