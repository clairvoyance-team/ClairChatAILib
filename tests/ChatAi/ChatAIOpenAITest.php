<?php
namespace tests\ChatAi;

use Clair\Ai\ChatAi\ChatAi;
use Clair\Ai\ChatAi\ChatHistory\ChatMessageHistory;
use Clair\Ai\ChatAi\LLM\OpenAIChatCompletion;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptTemplate;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;
use Clair\Ai\ChatAi\Prompt\HumanTextMessagePromptTemplate;
use Clair\Ai\ChatAi\Prompt\SystemMessagePromptTemplate;
use Clair\Ai\ChatAi\Tool\ToolFunction;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class ChatAIOpenAITest extends TestCase
{
    protected readonly OpenAIChatCompletion $open_ai_chat;
    public function setUp(): void
    {
        $this->open_ai_chat = OpenAIChatCompletion::from("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");
    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("テキスト単体の会話")]
    public function plainText() {
        $ChatAi = new ChatAi($this->open_ai_chat, ["model" => "gpt-3.5-turbo"]);
        $response = $ChatAi->send("沖縄のおすすめの料理を教えて");
        $response_text = $response->getContents();
        echo $response_text;

        $this->assertIsString($response_text);
    }

    #[Testdox("プロンプトを含めた会話")]
    public function ChatPrompt() {
        $ChatAi = new ChatAi($this->open_ai_chat, ["model" => "gpt-3.5-turbo", "max_tokens" => 500, "n" => 2]);
        $prompt = new ChatPromptTemplate([
            new SystemMessage("あなたは日本語を使うアシスタントです"),
            new HumanMessage("沖縄のおすすめの料理を教えて")
        ]);
        $response = $ChatAi->send($prompt);
        $response_text = $response->getContents();
        echo $response_text;

        $this->assertIsString($response_text);

        $expected_prompt = new ChatPromptValue([
            new SystemMessage("あなたは日本語を使うアシスタントです"),
            new HumanMessage("沖縄のおすすめの料理を教えて")
        ]);
        $this->assertEquals($expected_prompt, $response->prompt_value);
    }

    #[Testdox("プロンプトテンプレートを含めた会話")]
    public function ChatPromptTemplate() {
        $ChatAi = new ChatAi($this->open_ai_chat, ["model" => "gpt-3.5-turbo", "presence_penalty" => 0.3]);
        $prompt = new ChatPromptTemplate([
            new SystemMessagePromptTemplate("あなたは{input_language}を{output_language}に翻訳するアシスタントです"),
            new HumanTextMessagePromptTemplate("次の文章を{output_language}に翻訳して「おすすめの料理を教えて」")
        ]);
        $response = $ChatAi->send($prompt, ["input_language" => "日本語", "output_language" => "英語"]);
        $response_text = $response->getContents();
        echo $response_text;

        $this->assertIsString($response_text);

        $expected_prompt = new ChatPromptValue([
            new SystemMessage("あなたは日本語を英語に翻訳するアシスタントです"),
            new HumanMessage("次の文章を英語に翻訳して「おすすめの料理を教えて」")
        ]);
        $this->assertEquals($expected_prompt, $response->prompt_value);
    }

    /**
     * @throws ReflectionException
     * @throws MissingInputVariablesException
     */
    #[Testdox("ツール実行とツールを絶対に使用するように指定した会話")]
    public function ToolChat() {
        $weather = new TestWeatherForecaster("晴子ちゃん");
        $tool = [ToolFunction::readMethod($weather, "getCurrentWeather")];
        $ChatAi = new ChatAi($this->open_ai_chat, ["model" => "gpt-4-turbo", "tool_choice" => "required"], $tool);
        $response = $ChatAi->send("今日の東京の天気を教えて");
        print_r($response->getTools());
        $result = $response->runTools();
        print_r($result);

        $this->assertIsString($result[0]["tool_call_id"]);
        $this->assertIsString($result[0]["result"]);
        $this->assertMatchesRegularExpression("/晴子ちゃん.+は晴れです/", $result[0]["result"]);
    }

    /**
     * @throws ReflectionException
     * @throws MissingInputVariablesException
     */
    #[Testdox("ツールを実行し結果をgptに送って返信を取得する")]
    public function runToolAndSendResult() {
        $tool = [ToolFunction::readMethod("tests\ChatAi\TestWeatherForecaster", "getCurrentTemperature")];
        $ChatAi = new ChatAi($this->open_ai_chat, ["model" => "gpt-4-turbo"], $tool);
        $response = $ChatAi->send("今日の東京の気温を教えて");

        if ($response->getTools()) {
            //ツールを呼び出した場合
            $result = $ChatAi->runToolsAndSendResult($response);
            if ($result->getTools()) {

                $this->assertInstanceOf(ToolCallingContent::class, $result->getTools()[0]);
            } else {
                $this->assertIsString($result->getContents());
                print_r($result->getContents());
            }

        } else {
            //テキストが返ってきたとき
            $this->assertIsString($response->getContents());
            print_r($response->getContents());
        }
    }

    /**
     * @throws ReflectionException
     * @throws MissingInputVariablesException
     */
    #[Testdox("ツールとプロンプト+履歴を含めた会話")]
    public function ToolAndHistoryChat() {
        $weather = new TestWeatherForecaster("晴子ちゃん");
        $tools = [
            ToolFunction::readMethod($weather, "getCurrentWeather"),
            ToolFunction::readMethod("tests\ChatAi\TestWeatherForecaster", "getChanceOfRain")
        ];
        $ChatAi = new ChatAi($this->open_ai_chat, ["model" => "gpt-4-turbo"], $tools);
        $prompt = new ChatPromptTemplate([
            new SystemMessage("あなたは気象予報士アシスタントです"),
            new HumanTextMessagePromptTemplate("{when}の{location}の天気を教えて")
        ]);
        $response = $ChatAi->send($prompt, ["when" => "今日", "location" => "東京"]);

        if ($response->getTools()) {
            $tools_result = $response->runTools();
            $result = $tools_result[0]["result"];
        } else {
            $result = $response->getContents();
        }

        $this->assertIsString($result);

        $expected_prompt = new ChatPromptValue([
            new SystemMessage("あなたは気象予報士アシスタントです"),
            new HumanMessage("今日の東京の天気を教えて")
        ]);
        $this->assertEquals($expected_prompt, $response->prompt_value);
    }

}

class TestWeatherForecaster {

    public function __construct(
        public readonly string $name
    ) {
    }

    /**
     * 現在の天気を取得する
     * @param string $location 日本語の文字列で場所を示す
     * @param string $unit
     * @return string
     */
    public function getCurrentWeather(string $location, string $unit) :string
    {
        $str = $this->name . ":" . $location . "は晴れです";
        echo $str;
        return $str;
    }

    /**
     * @param string $location
     * @param string $format
     * @return string
     */
    public static function getCurrentTemperature(string $location, string $format="celsius"): string
    {
        $str = $location . "::24度";
        echo $str;
        return $str;
    }

    public static function getChanceOfRain(string $location, string $when): string
    {
        $str = $when . "の" . $location . "の降水確率は20％です";
        echo $str;
        return $str;
    }
}