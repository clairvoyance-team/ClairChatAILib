<?php

namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\ChatHistory\ChatHistory;
use Clair\Ai\ChatAi\Message\Message;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;

class ChatPromptTemplate
{

    public readonly array $input_variables;

    /**
     * @var (BaseMessagePromptTemplate|Message)[]
     */
    protected array $messages;

    public function __construct(array $messages)
    {
        $input_variables = [];
        foreach ($messages as $message) {
            if ($message instanceof BaseMessagePromptTemplate) {
                $input_variables = array_merge($input_variables, $message->input_variables);
            }

            if (!$message instanceof BaseMessagePromptTemplate && !$message instanceof Message) {
                throw new \InvalidArgumentException("第一引数はBaseMessagePromptTemplateかMessageの配列である必要があります");
            }
        }

        //重複を削除し、キーを振り直す
        $this->input_variables = array_values(array_unique($input_variables));

        $this->messages = $messages;
    }

    /**
     * ほぼデバッグ用
     * @return (BaseMessagePromptTemplate|Message)[]
     */
    public function getPromptMessages() {
        return $this->messages;
    }

    /**
     * @param ChatHistory $chatHistory
     * @return void
     */
    public function appendChatHistory(ChatHistory $chatHistory) :void
    {
        $this->messages = array_merge($this->messages, $chatHistory->toArray());
    }

    /**
     * @param array{string: mixed} $arguments
     * @throws MissingInputVariablesException
     * @return ChatPromptValue
     */
    public function formatPrompt(array $arguments): ChatPromptValue
    {
        $format_messages = [];
        foreach ($this->messages as $message) {
            if ($message instanceof BaseMessagePromptTemplate) {
                $format_messages = array_merge($format_messages, $message->formatMessages($arguments));
            } else {
                $format_messages = array_merge($format_messages, $message);
            }
        }

        return new ChatPromptValue($format_messages);
    }
}