<?php

namespace Clair\Ai\ChatAi\Message\Content;

use Clair\Ai\ChatAi\LLM\ChatLLM;

/**
 * メッセージの内容自体を表す
 */
interface Content
{
    /**
     * @return array
     */
    public function convertAPIRequest(ChatLLM $llm): array;

    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function formatLog(): string;

}