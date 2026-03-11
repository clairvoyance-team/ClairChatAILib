<?php
namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Message\UserMessage;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;

class UserMessagePromptTemplate extends BaseTextMessagePromptTemplate
{

    /**
     * @param array<string, mixed> $arguments
     * @return SystemMessage[]
     * @throws MissingInputVariablesException
     */
    public function formatMessages(array $arguments = []): array
    {
        $text_body = $this->assign($arguments);
        return [new UserMessage($text_body, $this->name)];
    }

}