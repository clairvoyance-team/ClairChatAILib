<?php

namespace Clair\Ai\ChatAi\LLM;

use Clair\Ai\ChatAi\LLM\Exception\InvalidParameterException;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Message\ToolMessage;
use Clair\Ai\ChatAi\Message\UserMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;

use Clair\Ai\ChatAi\Tool\Tool;
use OpenAI;
use OpenAI\Client;

class OpenAIChatCompletion implements ChatLLM
{

    public function __construct(
        private readonly Client $client,
    )
    {
    }

    public static function from(string $api_key): self
    {
        return new self(OpenAI::client($api_key));
    }


    /**
     * ユーザは主にこの関数を使う
     * @param array $params
     * @param ChatPromptValue $prompt
     * @param Tool[]|null $tools
     * @return LLMResult
     */
    public function generate(array $params, ChatPromptValue $prompt, array $tools = null): LLMResult
    {
        $params = new OpenAIChatCompletionParameters($params);

        $request_arr = $params->toRequestArr();
        $request_arr["model"] = $params->model;
        $request_arr["messages"] = $this->convertChatPromptToArr($prompt);
        if (!is_null($tools)) {
            $request_arr["tools"] = array_map(fn($tool) => $tool->toRequestArr(), $tools);
        }


        /*
        echo "OpenAIChatCompletion::generate:\n";
        print_r($request_arr);
        */


        $response = $this->client->chat()->create($request_arr);
        return new OpenAIResult($response, $tools);
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

            $convert_message_arr = match ($message::class) {
                SystemMessage::class => $this->convertSystemMessageToArr($message),
                HumanMessage::class => $this->convertHumanMessageToArr($message),
                AIMessage::class => $this->convertAIMessageToArr($message),
                ToolMessage::class => $this->convertToolMessageToArr($message),
                DeveloperMessage::class => $this->convertDeveloperMessageToArr($message),
                UserMessage::class => $this->convertUserMessageToArr($message),
            };

            $request_messages_arr = array_merge($request_messages_arr, $convert_message_arr);
        }

        /*
        echo "OpenAIChatCompletion::convertChatPromptToArr:\n";
        print_r($request_messages_arr);
        */

        return $request_messages_arr;
    }

    /**
     * SystemMessageをopenAIでリクエストする形式で返す
     * @param SystemMessage $message
     * @return array
     */
    public function convertSystemMessageToArr(SystemMessage $message): array
    {
        $text = $message->contents->convertAPIRequest($this)["text"];
        $arr = ["role" => "system", "content" => $text];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }

        //messagesの中身として、配列でラップして返す
        return [$arr];
    }

    public function convertDeveloperMessageToArr(DeveloperMessage $message): array
    {
        $text = $message->contents->convertAPIRequest($this)["text"];
        $arr = ["role" => "developer", "content" => $text];
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
    public function convertHumanMessageToArr(HumanMessage $message): array
    {
        $convert_contents = [];
        foreach ($message->contents as $content) {
            $convert_contents[] = $content->convertAPIRequest($this);
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
    public function convertAIMessageToArr(AIMessage $message): array
    {
        $content_arr = [];
        $tool_calls = [];
        foreach ($message->contents as $content) {
            if ($content instanceof TextContent) {
                $content_arr[] = $content->convertAPIRequest($this)["text"];

            } else if ($content instanceof ToolCallingContent) {
                $tool_calls[] = $content->convertAPIRequest($this);
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
    public function convertToolMessageToArr(ToolMessage $message): array
    {
        $text = $message->contents->convertAPIRequest($this)["text"];
        $arr = ["role" => "tool", "content" => $text, "tool_call_id" => $message->tool_call_id];
        //messagesの中身として、配列でラップして返す
        return [$arr];
    }

    /**
     * @param array{text: string} $content_data
     * @return array
     */
    public function convertTextContentToArr(array $content_data): array
    {
        return ["type" => "text", "text" => $content_data["text"]];
    }

    public function convertUserMessageToArr(UserMessage $message): array
    {
        $text = $message->contents->convertAPIRequest($this)["text"];
        $arr = ["role" => "user", "content" => $text];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }

        //messagesの中身として、配列でラップして返す
        return [$arr];
    }


    /**
     * @param array{image_url: string, data: string, image_type: string} $content_data
     * @return array
     */
    public function convertImageContentToArr(array $content_data): array
    {
        if ($content_data["image_url"]) {
            $url = $content_data["image_url"];
        } else {
            $url = "data:{$content_data["image_type"]};base64,{$content_data["data"]}";
        }

        return ["type" => "image_url", "image_url" => ["url" => $url]];
    }

    /**
     * @param array{tool_type: string, tool_call_id: string, tool_name: string, tool_args: array} $content_data
     * @return array
     */
    public function convertToolCallingContentToArr(array $content_data): array
    {
        return [
            "id" => $content_data["tool_call_id"],
            "type" => $content_data["tool_type"],
            "function" => [
                "name" => $content_data["tool_name"],
                "arguments" => $content_data["tool_args"]
            ]
        ];
    }
}