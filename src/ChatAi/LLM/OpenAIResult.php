<?php

namespace Clair\Ai\ChatAi\LLM;

use Clair\Ai\ChatAi\Message\Message;
use Clair\Ai\ChatAi\Tool\Tool;
use OpenAI\Responses\Chat\CreateResponse;

class OpenAIResult implements LLMResult
{

    /**
     * @var OpenAIResponseChoice[]
     */
    public readonly array $choices;

    /**
     * @param CreateResponse $openai_response
     * @param Tool[] $tools
     */
    public function __construct(
        public readonly CreateResponse $openai_response,
        array|null $tools
    ) {

        $this->choices = array_map(function ($val) use ($tools) {
            return OpenAIResponseChoice::fromCreateResponseChoice($val, $tools);
        }, $this->openai_response->choices);

    }

}