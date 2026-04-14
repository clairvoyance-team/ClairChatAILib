<?php

namespace tests\ChatAi;

use Clair\Ai\ChatAi\ChatAi;
use Clair\Ai\ChatAi\LLM\Gemini\GeminiCompletion;
use Clair\Ai\ChatAi\LLM\Gemini\GeminiApiException;
use Clair\Ai\ChatAi\LLM\LocalLLM\LocalLLMCompletion;
use Clair\Ai\ChatAi\LLM\OpenAi\OpenAIChatCompletion;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptTemplate;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\Prompt\DeveloperMessagePromptTemplate;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;
use Clair\Ai\ChatAi\Prompt\HumanTextMessagePromptTemplate;
use Clair\Ai\ChatAi\Prompt\SystemMessagePromptTemplate;
use Clair\Ai\ChatAi\Tool\ToolFunction;
use OpenAI;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionException;
use Clair\Ai\Tests\TestWeatherForecaster;

#[Group('local-only')]
class ChatGeminiTest extends TestCase
{
    protected readonly GeminiCompletion $gemini_chat;

    public function setUp(): void
    {
        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        $this->gemini_chat = GeminiCompletion::from("https://generativelanguage.googleapis.com/v1beta/openai/v1/chat/completions", $apiKey);

    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("テキスト単体の会話")]
    public function test_gemini()
    {
        $ChatAi = new ChatAi($this->gemini_chat, ["model" => "gemini-3-flash-preview"]);
        $response = $ChatAi->send("沖縄のおすすめの料理を教えて");
        $response_text = $response->getContents();
        echo $response_text;

        $this->assertIsString($response_text);
    }

    #[Testdox("プロンプトを含めた会話")]
    public function test_ChatPrompt()
    {
        $ChatAi = new ChatAi($this->gemini_chat, ["model" => "gemini-3-flash-preview"]);
        $prompt = new ChatPromptTemplate([
            new SystemMessage("あなたは日本語を使うアシスタントです"),
            new DeveloperMessage("返答は30文字以内でお願いします"),
            new HumanMessage("沖縄のおすすめの料理を教えて")
        ]);
        $response = $ChatAi->send($prompt);
        $response_text = $response->getContents();
        echo $response_text;

        $this->assertIsString($response_text);

        $expected_prompt = new ChatPromptValue([
            new SystemMessage("あなたは日本語を使うアシスタントです"),
            new DeveloperMessage("返答は30文字以内でお願いします"),
            new HumanMessage("沖縄のおすすめの料理を教えて")
        ]);
        $this->assertEquals($expected_prompt, $response->prompt_value);
    }

    #[Testdox("プロンプトテンプレートを含めた会話")]
    public function test_ChatPromptTemplate()
    {
        $ChatAi = new ChatAi($this->gemini_chat, ["model" => "gemini-3-flash-preview"]);
        $prompt = new ChatPromptTemplate([
            new SystemMessagePromptTemplate("あなたは{input_language}を{output_language}に翻訳するアシスタントです"),
            new DeveloperMessagePromptTemplate("実際に存在する料理であること"),
            new HumanTextMessagePromptTemplate("次の文章を{output_language}に翻訳して「おすすめの料理を教えて」")
        ]);
        $response = $ChatAi->send($prompt, ["input_language" => "日本語", "output_language" => "英語"]);
        $response_text = $response->getContents();
        //echo $response_text;

        $this->assertIsString($response_text);

        $expected_prompt = new ChatPromptValue([
            new SystemMessage("あなたは日本語を英語に翻訳するアシスタントです"),
            new DeveloperMessage("実際に存在する料理であること"),
            new HumanMessage("次の文章を英語に翻訳して「おすすめの料理を教えて」")
        ]);
        $this->assertEquals($expected_prompt, $response->prompt_value);
    }
}

