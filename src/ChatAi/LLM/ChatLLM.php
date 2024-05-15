<?php

namespace Clair\Ai\ChatAi\LLM;

use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\Tool\Tool;

interface ChatLLM
{
    /**
     * @param array $params
     * @param ChatPromptValue $prompt
     * @param array|null $tools
     * @return LLMResult
     */
    public function generate(array $params, ChatPromptValue $prompt, array $tools=null): LLMResult;

    /**
     * @param SystemMessage $message
     * @return array
     */
    public function convertSystemMessageToArr(SystemMessage $message): array;

    /**
     * @param HumanMessage $message
     * @return array
     */
    public function convertHumanMessageToArr(HumanMessage $message): array;

    /**
     * @param AIMessage $message
     * @return array
     */
    public function convertAIMessageToArr(AIMessage $message): array;


    /**
     * @param array{text: string} $content_data
     * @return array
     */
    public function convertTextContentToArr(array $content_data): array;

    /**
     * @param array{image_url: string, data: string, image_type: string} $content_data
     * @return array
     */
    public function convertImageContentToArr(array $content_data): array;

    /**
     * @param array{tool_type: string, tool_call_id: string, tool_name: string, tool_args: array} $content_data
     * @return array
     */
    public function convertToolCallingContentToArr(array $content_data): array;
}