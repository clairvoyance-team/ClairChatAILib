<?php

namespace Clair\Ai\ChatAi\Message\Content;

class TextContent
{
    public function __construct(
        public readonly string $content
    ) {
    }


    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function formatLog(): string
    {
        return $this->content;
    }
}