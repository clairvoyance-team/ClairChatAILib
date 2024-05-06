<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\Message\Tool\ToolCall;
use Clair\Ai\ChatAi\Message\Tool\ToolType;

class ToolCallingMessage implements Message
{
    private string $type = 'tool-calling';

    /**
     * @param string $tool_call_id
     * @param ToolType $tool_type
     * @param ToolCall[] $tool_calls
     * @param string|null $name
     */
    public function __construct(
        public readonly string   $tool_call_id,
        public readonly ToolType $tool_type,
        public readonly array    $tool_calls,
        public readonly ?string  $name = null
    ) {
    }


    public function logFormat(): string
    {
        $output = "{$this->type}:";
        foreach ($this->tool_calls as $call) {
            $output .= $call->logFormat();
        }
        return $output;
    }

    public function getType(): string
    {
        return $this->type;
    }
}