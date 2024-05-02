<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\Message\Content\Content;

class SystemMessage implements Message
{
    private string $type = "system";


    public function __construct(
        public readonly Content $content,
        public readonly ?string $name = null
    ) {
    }
    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function formatChatML(): string
    {
        return "({$this->type}){$this->name}: {$this->content->formatLog()}\n";
    }

    public function getType(): string
    {
        return $this->type;
    }
}