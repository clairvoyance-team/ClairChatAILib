<?php

namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\Exception\InvalidArgumentException;
use Clair\Ai\ChatAi\Message\Message;

class ChatPromptValue
{

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
    }

    /**
     * @return Message[]
     */
    public function toMessages(): array
    {
        return $this->messages;
    }
}