<?php

namespace Clair\Ai\ChatAi\Message\Content;

class ImageContent
{
    public function __construct(
        public readonly string $image_url
    ) {
    }


    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function formatLog(): string
    {
        return $this->image_url;
    }
}