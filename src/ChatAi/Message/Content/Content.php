<?php

namespace Clair\Ai\ChatAi\Message\Content;

/**
 * メッセージの内容自体を表す
 */
interface Content
{

    /**
     * 閲覧可能なデータを渡す
     * @return array
     */
    public function getContents(): array;

    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function formatLog(): string;

}