<?php

namespace Clair\Ai\ChatAi\ChatHistory;

use Clair\Ai\ChatAi\Message\Message;

/**
 * 1回のメッセージを表す
 */
interface ChatHistory
{
    /**
     * メッセージを追加
     * @param Message[] $messages
     * @return void
     */
    public function addMessages(array $messages): void;

    /**
     * HumanMessageに代入して追加する
     * @param string $message_contents
     * @return void
     */
    public function addUserMessage(string $message_contents): void;

    /**
     * AiMessageに代入して追加する
     * @param string $message_contents
     * @return void
     */
    public function addAiMessage(string $message_contents): void;

    /**
     * 配列にして返す
     * @return array
     */
    public function toArray(): array;
}