<?php

namespace Clair\Ai\ChatAi\Message\Content;

/**
 * メッセージの内容自体を表す
 */
interface Content
{
    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function formatLog(): string;

}