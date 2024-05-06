<?php

namespace Clair\Ai\ChatAi\Message;

/**
 * 1回のメッセージを表す
 */
interface Message
{
    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function logFormat(): string;

    /**
     * メッセージタイプ
     * @return string
     */
    public function getType(): string;
}