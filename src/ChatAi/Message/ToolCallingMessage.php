<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\Message\Tool\ToolType;

class ToolCallingMessage implements Message
{
    private string $type = 'tool-calling';


    public function __construct(
        public readonly string $tool_call_id,
        public readonly ToolType $tool_type,
        public readonly ?string $name = null
    ) {
    }


    public function formatChatML(): string
    {
        return "ai: {$this->type}\n";
    }

    public function getType(): string
    {
        return $this->type;
    }
}