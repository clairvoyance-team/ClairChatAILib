<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\Tool\ToolCall;
use Clair\Ai\ChatAi\Tool\ToolType;

class ToolCallingMessage implements Message
{
    private string $type = 'tool-calling';

    /**
     * @param string $tool_call_id
     * @param ToolType $tool_type
     * @param ToolCall $tool_call
     * @param string|null $name
     */
    public function __construct(
        public readonly string   $tool_call_id,
        public readonly ToolType $tool_type,
        public readonly ToolCall $tool_call,
        public readonly ?string  $name = null
    ) {
    }


    public function logFormat(): string
    {
        $output = "{$this->type}:";
        $output .= $this->tool_call->logFormat();
        return $output;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getToolCallingArr() {
        $function = $this->tool_call->toJsonArr();
        return [
            "tool_call_id" => $this->tool_call_id,
            "type" => $this->tool_type->value,
            "tool_name" => $function["name"],
            "tool_args" => $function["args"]
        ];
    }
}