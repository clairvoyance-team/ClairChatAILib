<?php

namespace Clair\Ai\ChatAi\ChatHistory;

use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\Content;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\Message;
use Clair\Ai\ChatAi\Message\SystemMessage;

/**
 * 1回のメッセージを表す
 */
class ChatMessageHistory implements ChatHistory
{

    /**
     * @param Message[] $messages
     */
    public function __construct(
        public array $messages = []
    ) {
        foreach ($this->messages as $message) {
            if (!$message instanceof Message) {
                throw new \InvalidArgumentException("第一引数はMessage[]である必要があります");
            }
        }
    }

    /**
     * メッセージを追加
     * @param Message[] $messages
     * @return void
     */
    public function addMessages(array $messages): void
    {
        $this->messages = array_merge($this->messages, $messages);
    }

    /**
     * HumanMessageに代入して追加する
     * @param string|Content $message_contents
     * @return void
     */
    public function addUserMessage(string|Content $message_contents): void
    {
        $this->messages[] = new HumanMessage($message_contents);
    }

    /**
     * AiMessageに代入して追加する
     * @param string $message_contents
     * @return void
     */
    public function addAiMessage(string $message_contents): void
    {
        $this->messages[] = new AIMessage($message_contents);
    }

    /**
     * 配列にして返す
     * @return array
     */
    public function toArray(): array
    {
        return $this->messages;
    }
}