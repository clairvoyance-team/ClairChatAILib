<?php

namespace tests\ChatAi;

use Clair\Ai\ChatAi\ChatAi;
use Clair\Ai\ChatAi\LLM\Grok\GrokCompletion;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptTemplate;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[Group('local-only')]
class ChatGrokTest extends TestCase
{
    protected readonly GrokCompletion $grok_chat;
    protected string $model = 'grok-3-mini';

    protected function setUp(): void
    {
        $api_key = $_ENV['GROK_API_KEY'] ?? $_SERVER['GROK_API_KEY'] ?? getenv('GROK_API_KEY') ?: null;
        if (is_null($api_key) || $api_key === '') {
            $this->markTestSkipped('GROK_API_KEY が未設定のためスキップします。');
        }

        $env_model = $_ENV['GROK_MODEL'] ?? $_SERVER['GROK_MODEL'] ?? getenv('GROK_MODEL') ?: null;
        if (!empty($env_model)) {
            $this->model = $env_model;
        }

        $this->grok_chat = GrokCompletion::from($api_key);
    }

    #[TestDox('Grok APIへの実接続確認ができる')]
    public function test_realApiConnection(): void
    {
        $chat_ai = new ChatAi($this->grok_chat, ['model' => $this->model, 'max_tokens' => 80]);
        $response = $chat_ai->send('接続確認です。必ず「OK」のみ返してください。');

        $this->assertIsString($response->getContents());
        $this->assertNotSame('', trim((string) $response->getContents()));
        $this->assertNotSame('', $response->model_name);
        $this->assertGreaterThanOrEqual(0, $response->input_token);
        $this->assertGreaterThanOrEqual(0, $response->output_token);
    }

    #[TestDox('Grokでテキスト単体の会話ができる')]
    public function test_plainText(): void
    {
        $chat_ai = new ChatAi($this->grok_chat, ['model' => $this->model]);
        $response = $chat_ai->send('沖縄のおすすめの料理を教えて');
        $response_text = $response->getContents();

        $this->assertIsString($response_text);
    }

    #[TestDox('Grokでプロンプトを含めた会話ができる')]
    public function test_chatPrompt(): void
    {
        $chat_ai = new ChatAi($this->grok_chat, ['model' => $this->model]);
        $prompt = new ChatPromptTemplate([
            new SystemMessage('あなたは日本語を使うアシスタントです'),
            new DeveloperMessage('返答は30文字以内でお願いします'),
            new HumanMessage('沖縄のおすすめの料理を教えて'),
        ]);

        $response = $chat_ai->send($prompt);
        $response_text = $response->getContents();

        $this->assertIsString($response_text);

        $expected_prompt = new ChatPromptValue([
            new SystemMessage('あなたは日本語を使うアシスタントです'),
            new DeveloperMessage('返答は30文字以内でお願いします'),
            new HumanMessage('沖縄のおすすめの料理を教えて'),
        ]);
        $this->assertEquals($expected_prompt, $response->prompt_value);
    }
}

