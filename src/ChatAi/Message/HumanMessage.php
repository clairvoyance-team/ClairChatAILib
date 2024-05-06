<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\Message\Content\Content;
use Clair\Ai\ChatAi\Message\Content\TextContent;

class HumanMessage implements Message
{
    public readonly Content $content;

    public readonly ?string $name;

    private string $type = "human";

    /**
     * @param string|Content $content デフォルトはstringという意味
     * @param string|null $name
     */
    public function __construct(
        string|Content $content,
        ?string $name = null
    ) {
        $this->content = (is_string($content)) ? new TextContent($content) : $content;
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