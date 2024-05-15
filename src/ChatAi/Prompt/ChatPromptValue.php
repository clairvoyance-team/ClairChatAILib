<?php

namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\ChatHistory\ChatMessageHistory;
use Clair\Ai\ChatAi\Exception\InvalidArgumentException;
use Clair\Ai\ChatAi\Message\Message;
use Clair\Ai\ChatAi\Message\SystemMessage;

class ChatPromptValue
{

    /**
     * @var Message[]
     */
    public readonly array $messages;

    /**
     * @param Message[] $messages
     */
    public function __construct(
        array $messages
    )
    {
        foreach ($messages as $message) {
            if (!$message instanceof Message) throw new InvalidArgumentException();
        }
        $this->messages = $messages;
    }

    /**
     * @return ChatMessageHistory
     */
    public function getChatMessageHistory(): ChatMessageHistory
    {
        $history_messages = [];
        foreach ($this->messages as $message) {
            //システムメッセージはチャット履歴に含めない
            if ($message instanceof SystemMessage) continue;

            $history_messages[] = $message;
        }

        return new ChatMessageHistory($history_messages);
    }

    /**
     * @return SystemMessage
     */
    public function getSystemMessage(): ?SystemMessage
    {
        foreach ($this->messages as $message) {
            //システムメッセージはチャット履歴に含めない
            if ($message instanceof SystemMessage) {
                return $message;
            }
        }

        //SystemMessageは含まれてない
        return null;
    }
}