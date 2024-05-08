<?php

namespace Clair\Ai\ChatAi\LLM;

use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\Tool\Tool;

interface ChatLLM
{

    /**
     * @param ChatPromptValue $prompt
     * @param Parameters|null $params
     * @param Tool[]|null $tools
     * @return LLMResult
     */
    public function generate(ChatPromptValue $prompt, Parameters $params=null, array $tools=null): LLMResult;
}