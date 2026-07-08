<?php

namespace Clair\Ai\ChatAi\LLM\Grok;

use Clair\Ai\ChatAi\LLM\ChatLLM;
use Clair\Ai\ChatAi\LLM\LLMResult;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Message\ToolMessage;
use Clair\Ai\ChatAi\Message\UserMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;

class GrokCompletion implements ChatLLM
{
    public const DEFAULT_URL = 'https://api.x.ai/v1/chat/completions';

    public bool $streaming_response = false;

    public function __construct(
        private readonly string $api_key,
        private readonly string $url = self::DEFAULT_URL,
    ) {
    }

    public static function from(string $api_key, string $url = self::DEFAULT_URL): self
    {
        return new self($api_key, $url);
    }

    public function generate(array $params, ChatPromptValue $prompt, array $tools = null): LLMResult
    {
        $params_obj = new GrokCompletionParameters($params);

        $request_arr = $params_obj->toRequestArr();
        $request_arr['model'] = $params_obj->model;
        $request_arr['messages'] = $this->convertChatPromptToArr($prompt);

        if (!is_null($tools)) {
            $request_arr['tools'] = array_map(fn ($tool) => $tool->toRequestArr(), $tools);
        }

        $client = new \GuzzleHttp\Client();
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'json' => $request_arr,
            'http_errors' => false,
        ];

        try {
            $response = $client->post($this->url, $options);
            $status_code = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($status_code < 200 || $status_code >= 300) {
                $error_message = "Grok API error: HTTP {$status_code}";
                $error_detail = $body;

                try {
                    $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                    if (isset($json['error'])) {
                        $error_detail = $json['error'];
                    }
                } catch (\Throwable $e) {
                }

                throw new GrokApiException($error_message, $status_code, $error_detail);
            }

            $data = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
            return new GrokResult($data, $tools);
        } catch (GrokApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GrokApiException('Connection error: ' . $e->getMessage(), 0, null, $e);
        }
    }

    public function convertChatPromptToArr(ChatPromptValue $prompt): array
    {
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
        $text = $message->contents->convertAPIRequest($this)['text'];
        $arr = ['role' => 'system', 'content' => $text];
        if (!is_null($message->name)) {
            $arr['name'] = $message->name;
        }

        return [$arr];
    }

    public function convertDeveloperMessageToArr(DeveloperMessage $message): array
    {
        $text = $message->contents->convertAPIRequest($this)['text'];
        $arr = ['role' => 'developer', 'content' => $text];
        if (!is_null($message->name)) {
            $arr['name'] = $message->name;
        }

        return [$arr];
    }

    public function convertHumanMessageToArr(HumanMessage $message): array
    {
        $convert_contents = [];
        foreach ($message->contents as $content) {
            $convert_contents[] = $content->convertAPIRequest($this);
        }

        if (count($convert_contents) === 1
            && isset($convert_contents[0]['type'])
            && $convert_contents[0]['type'] === 'text') {
            $content_payload = $convert_contents[0]['text'];
        } else {
            $content_payload = $convert_contents;
        }

        $arr = ['role' => 'user', 'content' => $content_payload];
        if (!is_null($message->name)) {
            $arr['name'] = $message->name;
        }

        return [$arr];
    }

    public function convertAIMessageToArr(AIMessage $message): array
    {
        $content_arr = [];
        $tool_calls = [];
        foreach ($message->contents as $content) {
            if ($content instanceof TextContent) {
                $content_arr[] = $content->convertAPIRequest($this)['text'];
            } elseif ($content instanceof ToolCallingContent) {
                $tool_calls[] = $content->convertAPIRequest($this);
            }
        }

        $messages = [];

        if ($content_arr) {
            foreach ($content_arr as $content) {
                $content_messages = [
                    'role' => 'assistant',
                    'content' => $content,
                ];
                if (!is_null($message->name)) {
                    $content_messages['name'] = $message->name;
                }
                $messages[] = $content_messages;
            }
        }

        if ($tool_calls) {
            $tool_message = [
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => $tool_calls,
            ];
            if (!is_null($message->name)) {
                $tool_message['name'] = $message->name;
            }

            $messages[] = $tool_message;
        }

        return $messages;
    }

    public function convertToolMessageToArr(ToolMessage $message): array
    {
        $text = $message->contents->convertAPIRequest($this)['text'];
        $arr = ['role' => 'tool', 'content' => $text, 'tool_call_id' => $message->tool_call_id];
        return [$arr];
    }

    /**
     * @param array{text: string} $content_data
     */
    public function convertTextContentToArr(array $content_data): array
    {
        return ['type' => 'text', 'text' => $content_data['text']];
    }

    public function convertUserMessageToArr(UserMessage $message): array
    {
        $text = $message->contents->convertAPIRequest($this)['text'];
        $arr = ['role' => 'user', 'content' => $text];
        if (!is_null($message->name)) {
            $arr['name'] = $message->name;
        }

        return [$arr];
    }

    /**
     * @param array{image_url: string, data: string, image_type: string} $content_data
     */
    public function convertImageContentToArr(array $content_data): array
    {
        if ($content_data['image_url']) {
            $url = $content_data['image_url'];
        } else {
            $url = "data:{$content_data['image_type']};base64,{$content_data['data']}";
        }

        return ['type' => 'image_url', 'image_url' => ['url' => $url]];
    }

    /**
     * @param array{tool_type: string, tool_call_id: string, tool_name: string, tool_args: array} $content_data
     */
    public function convertToolCallingContentToArr(array $content_data): array
    {
        return [
            'id' => $content_data['tool_call_id'],
            'type' => $content_data['tool_type'],
            'function' => [
                'name' => $content_data['tool_name'],
                'arguments' => $content_data['tool_args'],
            ],
        ];
    }
}

