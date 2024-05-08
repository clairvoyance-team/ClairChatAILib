<?php

namespace Clair\Ai\ChatAi\LLM;

use Clair\Ai\ChatAi\LLM\Exception\InvalidParameterException;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;

use OpenAI;

class OpenAIChat
{

    public function __construct(
        private string $api_key,
        public readonly ?array $params=null
    ) {}


    /**
     * @param ChatPromptValue $prompt
     * @param array|null $tools
     * @return LLMResult
     * @throws InvalidParameterException
     */
    public function chatCompletion(ChatPromptValue $prompt, array $tools=null): LLMResult
    {
        $client = OpenAI::client($this->api_key);
        $parameter = new OpenAIChatCompletionParameters($this->params);
        return new OpenAIResult();
    }


    public function update_emotions(string $love, string $happiness, string $disgust, string $fear) {

    }
}