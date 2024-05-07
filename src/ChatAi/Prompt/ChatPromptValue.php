<?php

namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\ChatHistory\ChatHistory;
use Clair\Ai\ChatAi\Message\Message;

class ChatPromptValue
{

    /**
     * @param Message[] $messages
     */
    public function __construct(
        public readonly array $messages
    )
    {
    }

    /**
     * @return Message[]
     */
    public function toMessages(): array
    {
        return $this->messages;
    }
}