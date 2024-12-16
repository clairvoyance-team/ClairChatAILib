<?php
namespace tests\Prompt;

use Clair\Ai\ChatAi\ChatHistory\ChatMessageHistory;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptTemplate;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\Prompt\HumanTextMessagePromptTemplate;
use Clair\Ai\ChatAi\Prompt\SystemMessagePromptTemplate;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;


class ChatPromptTemplateTest extends TestCase
{

    protected ChatPromptTemplate $chatPrompt;

    public function setUp(): void
    {
        $template_system = "あなたは {input_language} を {output} に翻訳するアシスタントです。";
        $system_message_template = new SystemMessagePromptTemplate($template_system);

        $template_human = "以下の {input_language} を翻訳してください。また{num}文字以内に収めてください。";
        $human_message_template = new HumanTextMessagePromptTemplate($template_human);

        $human_message = new HumanMessage("I love cats.", "alice");

        $this->chatPrompt = new ChatPromptTemplate([$system_message_template, $human_message_template, $human_message]);
    }

    #[TestDox("正しいテンプレート変数の取得")]
    public function test_isCorrectVariable() {
        $expected_input_variables = ["input_language", "output", "num"];
        $this->assertSame($expected_input_variables, $this->chatPrompt->input_variables);
    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("入力値を代入しPromptValueで返ってくる/正しいMessageで返ってくる")]
    public function test_formatPrompt() {
        $input = ["input_language" => "英語", "output" => "フランス語", "num" => 200];
        $result = $this->chatPrompt->formatPrompt($input);

        $expected_obj = new ChatPromptValue([
            new SystemMessage("あなたは 英語 を フランス語 に翻訳するアシスタントです。"),
            new HumanMessage("以下の 英語 を翻訳してください。また200文字以内に収めてください。"),
            new HumanMessage("I love cats.", "alice")
        ]);

        $this->assertInstanceOf(ChatPromptValue::class, $result);
        $this->assertEquals($expected_obj, $result);
    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("入力値にテンプレート変数があり、入れ子になってても全てに代入できる1")]
    public function test_nestedVariables1() {
        $input = [
            "second_language" => "イタリア語or{input_language}",
            "input_language" => "英語",
            "output" => "フランス語と{third_language}と{second_language}",
            "num" => 200,
            "third_language" => "中国語"
        ];
        $result = $this->chatPrompt->formatPrompt($input);

        $expected_obj = new ChatPromptValue([
            new SystemMessage("あなたは 英語 を フランス語と中国語とイタリア語or英語 に翻訳するアシスタントです。"),
            new HumanMessage("以下の 英語 を翻訳してください。また200文字以内に収めてください。"),
            new HumanMessage("I love cats.", "alice")
        ]);

        $this->assertInstanceOf(ChatPromptValue::class, $result);
        $this->assertEquals($expected_obj, $result);
    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("入力値にテンプレート変数があり、入れ子になってても全てに代入できる　ネスト変数を入力値後半に")]
    public function test_nestedVariables2() {
        $input = [
            "input_language" => "英語もしくは{second_language}",
            "output" => "フランス語と{third_language}と{second_language}",
            "num" => 200,
            "second_language" => "イタリア語",
            "third_language" => "中国語"
        ];
        $result = $this->chatPrompt->formatPrompt($input);

        $expected_obj = new ChatPromptValue([
            new SystemMessage("あなたは 英語もしくはイタリア語 を フランス語と中国語とイタリア語 に翻訳するアシスタントです。"),
            new HumanMessage("以下の 英語もしくはイタリア語 を翻訳してください。また200文字以内に収めてください。"),
            new HumanMessage("I love cats.", "alice")
        ]);

        $this->assertInstanceOf(ChatPromptValue::class, $result);
        $this->assertEquals($expected_obj, $result);
    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("入れ子が解決しない場合は、1階層分だけ処理される")]
    public function test_nestedVariables3() {
        $input = [
            "input_language" => "英語もしくは{input_language}",
            "output" => "フランス語と{third_language}",
            "num" => 200,
            "third_language" => "中国語"
        ];
        $result = $this->chatPrompt->formatPrompt($input);

        $expected_obj = new ChatPromptValue([
            new SystemMessage("あなたは 英語もしくは{input_language} を フランス語と中国語 に翻訳するアシスタントです。"),
            new HumanMessage("以下の 英語もしくは{input_language} を翻訳してください。また200文字以内に収めてください。"),
            new HumanMessage("I love cats.", "alice")
        ]);

        $this->assertInstanceOf(ChatPromptValue::class, $result);
        $this->assertEquals($expected_obj, $result);
    }

    /**
     * @testdox テンプレート変数に対して入力値が足りなければエラーが出る
     * @throws MissingInputVariablesException
     */
    #[TestDox("テンプレート変数に対して入力値が足りなければエラーが出る")]
    public function test_throwIfArgumentMissing() {
        $this->expectException(MissingInputVariablesException::class);
        $input = ["input_language" => "英語", "num" => 200];
        $this->chatPrompt->formatPrompt($input);
    }

    /**
     * @testdox テンプレート変数に対して入力値が足りなければエラーが出る
     * @throws MissingInputVariablesException
     */
    #[TestDox("テンプレート変数内で入れ子になっている変数に対しても、入力値が足りなければエラーが出る")]
    public function test_throwIfArgumentInNestedMissing() {
        $this->expectException(MissingInputVariablesException::class);
        $input = ["input_language" => "英語もしくは{output}", "output" => "フランス語と{second_language}", "num" => 200];
        $this->chatPrompt->formatPrompt($input);
    }

    #[TestDox("チャット履歴を追加できる")]
    public function test_canAppendChatHistory() {
        $chat_history = new ChatMessageHistory();
        $chat_history->addAIMessage("AIです");
        $chat_history->addUserMessage("Userです");

        $this->chatPrompt->appendChatHistory($chat_history);

        $messages = $this->chatPrompt->getPromptMessages();
        $this->assertInstanceOf(SystemMessagePromptTemplate::class, $messages[0]);
        $this->assertInstanceOf(HumanTextMessagePromptTemplate::class, $messages[1]);
        $this->assertInstanceOf(HumanMessage::class, $messages[2]);
        $this->assertInstanceOf(AIMessage::class, $messages[3]);
        $this->assertEquals(new AIMessage("AIです"), $messages[3]);
        $this->assertInstanceOf(HumanMessage::class, $messages[4]);
        $this->assertEquals(new HumanMessage("Userです"), $messages[4]);
    }


    #[TestDox("PromptValueからログフォーマットに変換できる")]
    public function test_canConvertToLogFormat() {
        $prompt_value = new ChatPromptValue([
            new SystemMessage("あなたは 英語 を フランス語 に翻訳するアシスタントです。"),
            new HumanMessage("以下の 英語 を翻訳してください。また200文字以内に収めてください。"),
            new HumanMessage("I love cats.", "alice"),
            new AIMessage("J'aime les chats.")
        ]);

        $result = $prompt_value->logFormat();
        $expected = <<<EOL
(system): あなたは 英語 を フランス語 に翻訳するアシスタントです。

(human): 以下の 英語 を翻訳してください。また200文字以内に収めてください。

(human)alice: I love cats.

(ai): J'aime les chats.

EOL;
        $this->assertSame($expected, $result);
    }
}

