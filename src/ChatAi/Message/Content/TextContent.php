<?php

namespace Clair\Ai\ChatAi\Message\Content;

class TextContent implements Content
{
    public function __construct(
        public readonly string $content
    ) {
    }

    /**
     * @return array{text: string}
     */
    public function getContents(): array
    {
        return ["text" => $this->content];
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