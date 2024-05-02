<?php

namespace Clair\Ai\ChatAi\Message;

class ToolMessage implements Message
{
    private string $type = "tool";


    public function formatChatML(): string
    {
        return "{$this->type}: \n";
    }

    public function getType(): string
    {
        return $this->type;
    }
}