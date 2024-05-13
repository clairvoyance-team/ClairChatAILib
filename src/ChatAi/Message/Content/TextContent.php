<?php

namespace Clair\Ai\ChatAi\Message\Content;

use Clair\Ai\ChatAi\LLM\ChatLLM;

class TextContent implements Content
{
    public function __construct(
        public readonly string $content
    ) {
    }

    public function convertAPIRequest(ChatLLM $llm) :array
    {
        return $llm->convertTextContentToArr(["text" => $this->content]);
    }

    /**
     * @return array{text: string}
     */
    public function getContents(): array
    {
        return [
            "type" => "text",
            "text" => $this->content
        ];
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