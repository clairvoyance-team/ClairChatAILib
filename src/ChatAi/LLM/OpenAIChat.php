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
use Clair\Ai\ChatAi\Message\ToolMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;

use Clair\Ai\ChatAi\Tool\Tool;
use OpenAI;

class OpenAIChat
{

    public function __construct(
        private readonly string $api_key,
        public readonly ?array  $params=null
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

    /**
     * プロンプトをAPIで投げる形式に変換する
     * @param ChatPromptValue $prompt
     * @return array|array[]
     */
    public function convertChatPromptToArr(ChatPromptValue $prompt): array
    {
        //APIリクエストのmessages用のArr
        $request_messages_arr = [];
        foreach ($prompt->messages as $message) {
            if ($message instanceof SystemMessage) {
                $request_messages_arr = array_merge($request_messages_arr, $this->convertSystemMessageToArr($message));
            } else if ($message instanceof HumanMessage) {
                $request_messages_arr = array_merge($request_messages_arr, $this->convertHumanMessageToArr($message));
            } else if ($message instanceof AIMessage) {
                $request_messages_arr = array_merge($request_messages_arr, $this->convertAIMessageToArr($message));
            } else if ($message instanceof ToolMessage) {
                $request_messages_arr = array_merge($request_messages_arr, $this->convertToolMessageToArr($message));
            }
        }

        return $request_messages_arr;
    }

    /**
     * SystemMessageをopenAIでリクエストする形式で返す
     * @param SystemMessage $message
     * @return array
     */
    private function convertSystemMessageToArr(SystemMessage $message): array
    {
        $arr = ["role" => "system", "content" => $message->content->content];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }

        //messagesの中身として、配列でラップして返す
        return [$arr];
    }

    /**
     * HumanMessageをopenAIでリクエストする形式で返す
     * @param HumanMessage $message
     * @return array
     */
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

                $convert_contents[] = ["type" => "image_url", "image_url" => ["url" => $url]];
            }
        }

        $arr = ["role" => "user", "content" => $convert_contents];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }

        //messagesの中身として、配列でラップして返す
        return [$arr];
    }

    /**
     * AIMessageをopenAIでリクエストする形式で返す
     * @param AIMessage $message
     * @return array
     */
    private function convertAIMessageToArr(AIMessage $message): array
    {
        $content_arr = [];
        $tool_calls = [];
        foreach ($message->contents as $content) {
            if ($content instanceof TextContent) {
                $content_arr[] = $content->getContents()["text"];

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

        $messages = [];

        if ($content_arr) {
            foreach ($content_arr as $content) {
                $content_messages = [
                    "role" => "assistant",
                    "content" => $content
                ];
                if (!is_null($message->name)) {
                    $content_messages["name"] = $message->name;
                }
                $messages[] = $content_messages;
            }
        }

        if ($tool_calls) {
            $tool_message = [
                "role" => "assistant",
                "content" => null,
                "tool_calls" => $tool_calls
            ];
            if (!is_null($message->name)) {
                $tool_message["name"] = $message->name;
            }

            $messages[] = $tool_message;
        }

        return $messages;
    }

    /**
     * ToolMessageをopenAIでリクエストする形式で返す
     * @param ToolMessage $message
     * @return array
     */
    private function convertToolMessageToArr(ToolMessage $message): array
    {
        $arr = ["role" => "tool", "content" => $message->content->content, "tool_call_id" => $message->tool_call_id];
        //messagesの中身として、配列でラップして返す
        return [$arr];
    }
}