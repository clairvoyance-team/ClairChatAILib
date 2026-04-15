<?php

namespace Clair\Ai\Tests\LLM;

use Clair\Ai\ChatAi\ChatAi;
use Clair\Ai\ChatAi\LLM\Gemini\GeminiCompletion;
use Clair\Ai\ChatAi\LLM\LocalLLM\LocalLLMCompletion;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;
use OpenAI;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;


class GeminiLLMTest extends TestCase
{
    protected GeminiCompletion $gemini_chat;

    protected function setUp(): void
    {
        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        $this->gemini_chat = GeminiCompletion::from("https://generativelanguage.googleapis.com/v1beta/openai/v1/chat/completions", $apiKey);
    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("システムメッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertSystemMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new SystemMessage("あなたは日本語を話すアドバイザーです。"),
            new DeveloperMessage("あんまり変なこと言わないでね。"),
            new HumanMessage("こんにちは！")
        ]);
        $result = $this->gemini_chat->convertChatPromptToArr($prompt_value);

        // 文字列で返ってくることを期待する
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
                "content" => "こんにちは！" // ← 配列から文字列に修正
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
        $result = $this->gemini_chat->convertChatPromptToArr($prompt_value);

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

    /* 実際の送信はChatAi/でやる
    #[TestDox("テキスト単体の会話")]
    public function test_plain() {
        $ChatAi = new ChatAi($this->openAIChat, ["model" => "huihui-ai/Qwen2.5-14B-Instruct-abliterated-v2", "temperature" => 0.3]);
        $response = $ChatAi->send("沖縄のおすすめの料理を教えて");
        $response_text = $response->getContents();
        echo $response_text;

        $this->assertIsString($response_text);
    }*/
}