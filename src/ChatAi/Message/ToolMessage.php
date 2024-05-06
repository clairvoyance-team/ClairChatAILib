<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\Message\Content\Content;
use Clair\Ai\ChatAi\Message\Content\TextContent;

class ToolMessage implements Message
{
    public readonly Content $content;

    private string $type = "tool";

    public function __construct(
        string  $content,
        public readonly string  $tool_call_id,
        public readonly ?string $name = null
    ) {
        $this->content = new TextContent($content);
    }


    public function logFormat(): string
    {
        return "{$this->type}: {$this->content}(tool_call_id: {$this->tool_call_id})\n";
    }

    public function getType(): string
    {
        return $this->type;
    }
}