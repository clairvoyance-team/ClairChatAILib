<?php

namespace Clair\Ai\ChatAi\LLM;

use Clair\Ai\ChatAi\LLM\Exception\InvalidParameterException;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\ImageContent;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Message\ToolCallingMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;

use Clair\Ai\ChatAi\Tool\Tool;
use OpenAI;

class OpenAIChat
{

    public function __construct(
        private string $api_key,
        public readonly ?array $params=null
    ) {}


    /**
     * @param ChatPromptValue $prompt
     * @param Tool[]|null $tools
     * @return LLMResult
     * @throws InvalidParameterException
     */
    public function chatCompletion(ChatPromptValue $prompt, array $tools=null): LLMResult
    {
        $client = OpenAI::client($this->api_key);
        $parameter = new OpenAIChatCompletionParameters($this->params);


        return new OpenAIResult();
    }

    private function convertSystemMessageToArr(SystemMessage $message) {
        $arr = ["role" => "system", "content" => $message->content];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }
        return $arr;
    }

    private function convertHumanMessageToArr(HumanMessage $message): array
    {
        $convert_contents = [];
        foreach ($message->contents as $content) {
            if ($content instanceof TextContent) {
                $convert_contents[] = ["type" => "text", "text" => $content->getContents()["text"]];

            } else if ($content instanceof ImageContent) {
                $info = $content->getContents();
                $url = "";
                if ($info["image_url"]) {
                    $url = $info["image_url"];
                } else if ($info["data"]) {
                    $url = "data:{$info["image_type"]};base64,{$info["data"]}";
                }

                $convert_contents[] = ["type" => "image_url", "url" => $url];

            }
        }

        $arr = ["role" => "user", "content" => $convert_contents];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }

        //messagesの中身として、配列でラップして返す
        return [$arr];
    }

    private function convertAIMessageToArr(AIMessage $message): array
    {
        $content_arr = [];
        $tool_calls = [];
        foreach ($message->contents as $content) {
            if ($content instanceof TextContent) {
                $content_arr[] = ["type" => "text", "text" => $content->getContents()["text"]];

            } else if ($content instanceof ToolCallingContent) {
                $tool_content = $content->getContents();

                $tool_calls[] = [
                    "id" => $tool_content["tool_call_id"],
                    "type" => $tool_content["type"],
                    "function" => [
                        "name" => $tool_content["tool_name"],
                        "arguments" => $tool_content["tool_args"]
                    ]
                ];
            }
        }

        $arr = [];
        if ($content_arr) {
            $arr = [
                "role" => "assistant",
                "content" => $content_arr
            ];
        }

        $arr = ["role" => "assistant", "content" => $message->contents];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }
        return $arr;
    }

    private function convertToolCallingMessage(ToolCallingMessage $message) {
        $tool_calls = $message->getToolCallingArr();

        $arr = ["role" => "assistant", "content" => null];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }
        return $arr;
    }
}