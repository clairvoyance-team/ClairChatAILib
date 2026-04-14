<?php

namespace tests\ChatAi;

use Clair\Ai\ChatAi\ChatAi;
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
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionException;
use Clair\Ai\Tests\TestWeatherForecaster;

#[Group('local-only')]
class ChatAILocalLLMTest extends TestCase
{
    protected readonly LocalLLMCompletion $local_llm_chat;
    protected string $model = "local-llm-test-model";

    public function setUp(): void
    {
        /*
        var_dump($_ENV['OPEN_AI_API_KEY'] ?? null);
        var_dump($_SERVER['OPEN_AI_API_KEY'] ?? null);
        var_dump(getenv('OPEN_AI_API_KEY'));
        */

        $apiKey = $_ENV['LOCAL_LLM_API_KEY'] ?? null;

        $this->local_llm_chat = LocalLLMCompletion::from("http://118.238.8.76:8080/v1/chat/completions/", $apiKey);
    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("テキスト単体の会話")]
    public function test_plainText()
    {
        $ChatAi = new ChatAi($this->local_llm_chat, ["model" => $this->model]);
        $response = $ChatAi->send("沖縄のおすすめの料理を教えて");
        $response_text = $response->getContents();
        echo $response_text;

        $this->assertIsString($response_text);
    }

    #[Testdox("プロンプトを含めた会話")]
    public function test_ChatPrompt()
    {
        $ChatAi = new ChatAi($this->local_llm_chat, ["model" => $this->model, "max_tokens" => 500, "n" => 2]);
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
        $ChatAi = new ChatAi($this->local_llm_chat, ["model" => $this->model, "presence_penalty" => 0.3]);
        $prompt = new ChatPromptTemplate([
            new SystemMessagePromptTemplate("あなたは{input_language}を{output_language}に翻訳するアシスタントです"),
            new DeveloperMessagePromptTemplate("実際に存在する料理であること"),
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
    public function test_ToolChat()
    {
        $weather = new TestWeatherForecaster("晴子ちゃん");
        $tool = [ToolFunction::readMethod($weather, "getCurrentWeather")];
        $ChatAi = new ChatAi($this->local_llm_chat, ["model" => $this->model, "tool_choice" => "required"], $tool);
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
    public function test_runToolAndSendResult()
    {
        $tool = [ToolFunction::readMethod("Clair\\Ai\\Tests\\TestWeatherForecaster", "getCurrentTemperature")];
        $ChatAi = new ChatAi($this->local_llm_chat, ["model" => $this->model], $tool);
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
    public function test_ToolAndHistoryChat()
    {
        $weather = new TestWeatherForecaster("晴子ちゃん");
        $tools = [
            ToolFunction::readMethod($weather, "getCurrentWeather"),
            ToolFunction::readMethod("Clair\\Ai\\Tests\\TestWeatherForecaster", "getChanceOfRain")
        ];
        $ChatAi = new ChatAi($this->local_llm_chat, ["model" => $this->model], $tools);
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

    /**
     * @throws ReflectionException
     * @throws MissingInputVariablesException
     */
    #[Testdox("JsonSchemaを使ったレスポンス")]
    public function test_JsonSchemaResponse()
    {
        $json_schema = [
            'name' => 'check_non_japanese',
            'strict' => true,
            'schema' =>
                [
                    'type' => 'object',
                    'required' =>
                        [
                            0 => 'un_japanese',
                            1 => 'un_japanese_integer',
                        ],
                    'properties' =>
                        [
                            'un_japanese' =>
                                [
                                    'type' => 'boolean',
                                    'description' => '理解できる日本語か判定してください。理解できればtrue、理解できなければfalseを返してください',
                                ],
                            'un_japanese_integer' =>
                                [
                                    'type' => 'integer',
                                    'description' => '理解できる日本語か0から9の10段階で判定してください。理解できる場合は9、理解できない場合は0で返してください',
                                ],
                        ],
                    'additionalProperties' => false,
                ],
        ];

        $ChatAi = new ChatAi($this->local_llm_chat, ["model" => $this->model, "response_format" => ["type" => "json_schema", "json_schema" => $json_schema]]);
        $prompt = new ChatPromptTemplate([
            new SystemMessage("あなたはユーザーの日本語を厳しくチェックする先生です。"),
            new HumanTextMessagePromptTemplate("えーっと。。恥ずかしいなぁwwみーちゃんがそんな事聞くなんて意外だよ？？angerschaft")
        ]);
        $response = $ChatAi->send($prompt);

        $result = $response->getContents();

        $json_array = json_decode($result, true);
        $this->assertIsArray($json_array);
        $this->assertFalse($json_array["un_japanese"]);
        $this->assertIsInt($json_array["un_japanese_integer"]);
    }

}