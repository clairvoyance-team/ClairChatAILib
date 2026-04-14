<?php
namespace tests\LLM;

use Clair\Ai\ChatAi\LLM\OpenAi\OpenAIChatCompletion;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\ImageContent;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Message\ToolMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\Tool\ToolFunction;
use Clair\Ai\ChatAi\Tool\ToolFunctionCall;
use Clair\Ai\ChatAi\Tool\ToolType;
use OpenAI;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class OpenAIChatTest extends TestCase
{

    protected OpenAIChatCompletion $openAIChat;
    protected function setUp(): void
    {
        $this->openAIChat = new OpenAIChatCompletion(OpenAI::client("random"));
    }

    #[TestDox("システムメッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertSystemMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new SystemMessage("あなたは日本語を話すアドバイザーです。"),
            new DeveloperMessage("あんまり変なこと言わないでね。"),
            new HumanMessage("こんにちは！")
        ]);
        $result = $this->openAIChat->convertChatPromptToArr($prompt_value);
        fwrite(STDERR, print_r($result, true));

        $expected = [
            [
                "role" => "system",
                "content" => "あなたは日本語を話すアドバイザーです。"
            ],
            [
                "role" => "developer",
                "content" => "あんまり変なこと言わないでね。"
            ],
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "text",
                        "text" => "こんにちは！"
                    ]
                ]
            ]
        ];

        $this->assertSame($expected, $result);
    }

    #[TestDox("ユーザの画像(URL)メッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertHumanURLImageMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new SystemMessage("あなたは日本語を話すアドバイザーです。"),
            new HumanMessage([
                new TextContent("この画像の場所の現在の天気を教えてください"),
                new ImageContent("https://example.com/Marunouchi.jpg")
            ])
        ]);
        $result = $this->openAIChat->convertChatPromptToArr($prompt_value);

        $expected = [
            [
                "role" => "system",
                "content" => "あなたは日本語を話すアドバイザーです。"
            ],
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "text",
                        "text" => "この画像の場所の現在の天気を教えてください"
                    ],
                    [
                        "type" => "image_url",
                        "image_url" => ["url" => "https://example.com/Marunouchi.jpg"]
                    ]
                ]
            ]
        ];

        $this->assertSame($expected, $result);
    }

    #[TestDox("ユーザの画像(base64データ)メッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertHumanDataImageMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new HumanMessage([
                new ImageContent(null, "iVBORw0KGgoAAAANSUhEUgAAAM", "image/jpeg")
            ])
        ]);
        $result = $this->openAIChat->convertChatPromptToArr($prompt_value);

        $expected = [
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "image_url",
                        "image_url" => ["url" => "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAM"]
                    ]
                ]
            ]
        ];

        $this->assertSame($expected, $result);
    }

    #[TestDox("AI複数テキストメッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertAITextMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new AIMessage([
                new TextContent("こんにちは、アシスタントです。"),
                new TextContent("何かご用ですか？")
            ])
        ]);
        $result = $this->openAIChat->convertChatPromptToArr($prompt_value);

        $expected = [
            [
                "role" => "assistant",
                "content" => "こんにちは、アシスタントです。"
            ],
            [
                "role" => "assistant",
                "content" => "何かご用ですか？"
            ],
        ];

        $this->assertSame($expected, $result);
    }

    #[TestDox("AI複数Tool呼び出しメッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertAIToolCallingMessage(): void
    {
        $stub_tool_function = $this->createStub(ToolFunction::class);

        $prompt_value = new ChatPromptValue([
            new AIMessage([
                new ToolCallingContent(
                    "call_abc123",
                    ToolType::Function,
                    new ToolFunctionCall("get_current_weather", ["location" => "Tokyo"], $stub_tool_function),
                ),
                new ToolCallingContent(
                    "call_abc456",
                    ToolType::Function,
                    new ToolFunctionCall("get_current_temperature", ["location" => "Tokyo"], $stub_tool_function),
                ),
            ])
        ]);
        $result = $this->openAIChat->convertChatPromptToArr($prompt_value);

        $expected = [
            [
                "role" => "assistant",
                "content" => null,
                "tool_calls" => [
                    [
                        "id" => "call_abc123",
                        "type" => "function",
                        "function" => [
                            "name" => "get_current_weather",
                            "arguments" => '{"location":"Tokyo"}'
                        ]
                    ],
                    [
                        "id" => "call_abc456",
                        "type" => "function",
                        "function" => [
                            "name" => "get_current_temperature",
                            "arguments" => '{"location":"Tokyo"}'
                        ]
                    ]
                ]
            ]
        ];

        $this->assertSame($expected, $result);
    }

    #[TestDox("Tool結果メッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertToolMessage(): void
    {
        $stub_tool_function = $this->createStub(ToolFunction::class);

        $prompt_value = new ChatPromptValue([
            new AIMessage([
                new ToolCallingContent(
                    "call_abc123",
                    ToolType::Function,
                    new ToolFunctionCall("get_current_weather", ["location" => "Tokyo"], $stub_tool_function),
                ),
            ]),
            new ToolMessage("rainy", "call_abc123"),
        ]);
        $result = $this->openAIChat->convertChatPromptToArr($prompt_value);

        $expected = [
            [
                "role" => "assistant",
                "content" => null,
                "tool_calls" => [
                    [
                        "id" => "call_abc123",
                        "type" => "function",
                        "function" => [
                            "name" => "get_current_weather",
                            "arguments" => '{"location":"Tokyo"}'
                        ]
                    ]
                ]
            ],
            [
                "role" => "tool",
                "content" => "rainy",
                "tool_call_id" => "call_abc123"
            ]
        ];

        $this->assertSame($expected, $result);
    }
}

