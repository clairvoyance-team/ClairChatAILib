<?php

namespace tests\LLM;

use Clair\Ai\ChatAi\LLM\Grok\GrokCompletion;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class GrokLLMTest extends TestCase
{
    protected GrokCompletion $grok_chat;

    protected function setUp(): void
    {
        $this->grok_chat = GrokCompletion::from('dummy-test-key');
    }

    #[TestDox('システム/デベロッパー/ユーザーメッセージをAPIリクエストに変換できる')]
    public function test_canConvertSystemMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new SystemMessage('あなたは日本語を話すアドバイザーです。'),
            new DeveloperMessage('あんまり変なこと言わないでね。'),
            new HumanMessage('こんにちは！'),
        ]);

        $result = $this->grok_chat->convertChatPromptToArr($prompt_value);

        $expected = [
            [
                'role' => 'system',
                'content' => 'あなたは日本語を話すアドバイザーです。',
            ],
            [
                'role' => 'developer',
                'content' => 'あんまり変なこと言わないでね。',
            ],
            [
                'role' => 'user',
                'content' => 'こんにちは！',
            ],
        ];

        $this->assertSame($expected, $result);
    }

    #[TestDox('AIの複数テキストメッセージをAPIリクエストに変換できる')]
    public function test_canConvertAITextMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new AIMessage([
                new TextContent('こんにちは、アシスタントです。'),
                new TextContent('何かご用ですか？'),
            ]),
        ]);

        $result = $this->grok_chat->convertChatPromptToArr($prompt_value);

        $expected = [
            [
                'role' => 'assistant',
                'content' => 'こんにちは、アシスタントです。',
            ],
            [
                'role' => 'assistant',
                'content' => '何かご用ですか？',
            ],
        ];

        $this->assertSame($expected, $result);
    }
}

