<?php
namespace tests\Prompt;

use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\SystemMessagePromptTemplate;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;


class MessagePromptTest extends TestCase
{

    #[TestDox("正しいテンプレート変数の取得")]
    public function test_isCorrectVariable() {
        $template = "あなたは {input_language} を {output} に翻訳するアシスタントです。";
        $message_template = new SystemMessagePromptTemplate($template);

        $expected_variables = ["input_language", "output"];
        $this->assertSame($expected_variables, $message_template->input_variables);
    }

    #[TestDox("テンプレート変数に入力値を代入したSystemMessageが返される")]
    public function test_canAssign() {
        $template = "あなたは {input_language} を {output} に翻訳するアシスタントです。";
        $message_template = new SystemMessagePromptTemplate($template);

        $input_variables = ["input_language" => "英語", "output" => "フランス語"];
        $expected_obj = new SystemMessage("あなたは 英語 を フランス語 に翻訳するアシスタントです。");

        //戻り値はarray(SystemMessage)
        $test_result = $message_template->formatMessages($input_variables);

        $target = $test_result[0];
        $this->assertInstanceOf(SystemMessage::class, $target);
        $this->assertEquals($expected_obj, $target);
    }

    #[TestDox("テンプレート変数に対して入力値が足りなければエラーが出る")]
    public function test_throwIfArgumentMissing() {
        $this->expectException(MissingInputVariablesException::class);

        $template = "あなたは {input_language} を {output} に翻訳するアシスタントです。";
        $message_template = new SystemMessagePromptTemplate($template);

        $input_variables = ["input_language" => "英語"];
        $message_template->formatMessages($input_variables);
    }
}

