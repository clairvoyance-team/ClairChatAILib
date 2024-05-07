<?php
namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;

class HumanTextMessagePromptTemplate extends BaseTextMessagePromptTemplate
{

    /**
     * @param array{string: mixed} $arguments
     * @return HumanMessage[]
     * @throws MissingInputVariablesException
     */
    public function formatMessages(array $arguments = []): array
    {
        $text_body = $this->assign($arguments);
        return [new HumanMessage($text_body, $this->name)];
    }

}