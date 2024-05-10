<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\Message\Content\Content;
use Clair\Ai\ChatAi\Message\Content\TextContent;

class SystemMessage implements Message
{
    public readonly Content $content;

    public readonly ?string $name;
    private string $type = "system";


    public function __construct(
        string $content,
        ?string $name = null
    ) {
        $this->content = new TextContent($content);
        $this->name = $name;
    }

    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function logFormat(): string
    {
        return "({$this->type}){$this->name}: {$this->content->formatLog()}\n";
    }

    public function getType(): string
    {
        return $this->type;
    }
}