<?php

namespace Clair\Ai\ChatAi\LLM\Gemini;


use Clair\Ai\ChatAi\LLM\ChatLLM;
use Clair\Ai\ChatAi\LLM\LLMResult;
use Clair\Ai\ChatAi\LLM\LocalLLM\LocalLLMCompletionParameters;
use Clair\Ai\ChatAi\LLM\LocalLLM\LocalLLMResult;
use Clair\Ai\ChatAi\LLM\LocalLLM\LocalLLMStreamResult;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Message\ToolMessage;
use Clair\Ai\ChatAi\Message\UserMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\LLM\Gemini\GeminiApiException;
use OpenAI;
use OpenAI\Client;

class GeminiCompletion implements ChatLLM
{

    public bool $streaming_response = false;

    public function __construct(
        private readonly string $url,
        private readonly string $api_key,
    )
    {
    }

    public static function from(string $url,string $api_key): self
    {
        return new self($url, $api_key);
    }

    public function generate(array $params, ChatPromptValue $prompt, array $tools = null): LLMResult
    {
        $params_obj = new GeminiCompletionParameters($params);

        $request_arr = $params_obj->toRequestArr();
        $request_arr["model"] = $params_obj->model;
        $request_arr["messages"] = $this->convertChatPromptToArr($prompt);

        if (!is_null($tools)) {
            $request_arr["tools"] = array_map(fn($tool) => $tool->toRequestArr(), $tools);
        }

        $client = new \GuzzleHttp\Client();

        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'    => $request_arr["model"],
                'messages' => $request_arr["messages"],
                // SDKだとエラーになるような独自パラメータも自由に送れる
            ],
            'http_errors' => false, // 404などでも例外を投げずにレスポンスを取得する
        ];

        try {
            $response = $client->post($this->url, $options);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            // ステータスコードが200番台以外はエラーとして扱う
            if ($statusCode < 200 || $statusCode >= 300) {
                $errorMsg = "Gemini API error: HTTP $statusCode";
                // レスポンスボディがJSONなら詳細を付加
                $errorDetail = $body;
                try {
                    $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                    if (isset($json['error'])) {
                        $errorDetail = $json['error'];
                    }
                } catch (\Throwable $e) {
                    // JSONでなければそのまま
                }
                throw new GeminiApiException($errorMsg, $statusCode, $errorDetail);
            }
        } catch (\Exception $e) {
            // 接続自体に失敗（タイムアウトやDNSエラーなど）した場合
            throw new GeminiApiException('Connection error: ' . $e->getMessage(), 0, null, $e);
        }

        $data = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        return new GeminiResult($data, $tools);
    }

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

        return $request_messages_arr;
    }
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
        $arr = ["role" => "system", "content" => $text]; // geminiはdevelopperのroleないのです
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

        // --- ここから修正 ---
        // もしコンテンツが1つだけで、それがテキストなら文字列として取り出す
        // これにより curl と同じ {"role": "user", "content": "文字列"} の形になります
        if (count($convert_contents) === 1 && isset($convert_contents[0]['type']) && $convert_contents[0]['type'] === 'text') {
            $content_payload = $convert_contents[0]['text'];
        } else {
            // 画像などがある場合はそのまま配列で送る
            $content_payload = $convert_contents;
        }
        // --- ここまで修正 ---

        $arr = ["role" => "user", "content" => $content_payload];
        if (!is_null($message->name)) {
            $arr["name"] = $message->name;
        }

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