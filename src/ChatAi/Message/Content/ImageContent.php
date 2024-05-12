<?php

namespace Clair\Ai\ChatAi\Message\Content;

use Clair\Ai\ChatAi\Message\Exception\MissingDataException;

class ImageContent implements Content
{
    /**
     * @throws MissingDataException
     */
    public function __construct(
        public readonly ?string $image_url = null,
        public readonly ?string $data = null,
        public readonly ?string $image_type = null
    ) {
        if (is_null($this->image_url) && (is_null($data) || is_null($this->image_type))) {
            throw new MissingDataException("URLか画像データ自体が必要です。");
        }
    }

    /**
     * @return array{image_url: string, data: string, image_type: string}
     */
    public function getContents(): array
    {
        return [
            "image_url" => $this->image_url,
            "data" => $this->data,
            "image_type" => $this->image_type
        ];
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