<?php

namespace Clair\Ai\ChatAi;

use Clair\Ai\ChatAi\Exception\InvalidArgumentException;
use Clair\Ai\ChatAi\LLM\ChatLLM;
use Clair\Ai\ChatAi\Message\ToolMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptTemplate;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;
use Clair\Ai\ChatAi\Tool\Tool;

class ChatAi
{

    /**
     * @param ChatLLM $chat_llm
     * @param array $parameters
     * @param Tool[] $tools
     */
    public function __construct(
        private readonly ChatLLM $chat_llm,
        private readonly array $parameters,
        private readonly ?array $tools = null
    )
    {
        if (!is_null($this->tools)) {
            foreach ($this->tools as $tool) {
                if (!$tool instanceof Tool) throw new InvalidArgumentException("第三引数はTool型のリストである必要があります");
            }
        }
    }

    /**
     * @throws MissingInputVariablesException
     */
    public function send(ChatPromptTemplate|string $prompt, array $input_variables=[]): ChatAiResult
    {

        if (is_string($prompt)) {
            $prompt = ChatPromptTemplate::fromTemplate($prompt);
        }
        $prompt_value = $prompt->formatPrompt($input_variables);

        $response = $this->chat_llm->generate($this->parameters, $prompt_value, $this->tools);

        $usage = $response->getUsage();

        return new ChatAiResult(
            $response->getModelName(),
            $response->getChoices(),
            $prompt_value->getChatMessageHistory(),
            $response->getCreatedAt(),
            $usage["input_tokens"],
            $usage["output_tokens"]
        );
    }

//    public function runToolsAndSendResult(ChatAiResult $result) {
//        if (is_null($result->getTools())) {
//            return $result->getContents();
//        }
//
//        $tools_result = $result->runTools();
//        $tool_messages = [];
//        foreach ($tools_result as $tool) {
//            $tool_messages[] = new ToolMessage($tool["result"], $tool["tool_call_id"]);
//        }
//
//
//    }
}