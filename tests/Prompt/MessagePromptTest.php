<?php
namespace tests\Prompt;

use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\SystemMessagePromptTemplate;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;

use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Prompt\DeveloperMessagePromptTemplate;

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

    /**
     * @throws MissingInputVariablesException
     */
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

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("テンプレート変数がなくてもOK")]
    public function test_isNothingVariable() {
        $template = "あなたは英語を日本語に翻訳するアシスタントです。";
        $message_template = new SystemMessagePromptTemplate($template);

        $input_variables = ["input_language", "output"];
        $test_result = $message_template->formatMessages($input_variables);
        $expected_obj[] = new SystemMessage("あなたは英語を日本語に翻訳するアシスタントです。");
        $this->assertEquals($expected_obj, $test_result);
    }

    #[TestDox("正しいテンプレート変数の取得（DeveloperMessage）")]
    public function test_isCorrectVariable_DeveloperMessage() {
        $template = "開発者向け: {info} ({level})";
        $message_template = new DeveloperMessagePromptTemplate($template);

        $expected_variables = ["info", "level"];
        $this->assertSame($expected_variables, $message_template->input_variables);
    }

    #[TestDox("テンプレート変数に入力値を代入したDeveloperMessageが返される")]
    public function test_canAssign_DeveloperMessage() {
        $template = "開発者向け: {info} ({level})";
        $message_template = new DeveloperMessagePromptTemplate($template);

        $input_variables = ["info" => "デバッグ情報", "level" => "詳細"];
        $expected_obj = new DeveloperMessage("開発者向け: デバッグ情報 (詳細)");

        $test_result = $message_template->formatMessages($input_variables);
        $target = $test_result[0];
        $this->assertInstanceOf(DeveloperMessage::class, $target);
        $this->assertEquals($expected_obj, $target);
    }

    #[TestDox("テンプレート変数に対して入力値が足りなければエラーが出る（DeveloperMessage）")]
    public function test_throwIfArgumentMissing_DeveloperMessage() {
        $this->expectException(MissingInputVariablesException::class);

        $template = "開発者向け: {info} ({level})";
        $message_template = new DeveloperMessagePromptTemplate($template);

        $input_variables = ["info" => "デバッグ情報"];
        $message_template->formatMessages($input_variables);
    }

    #[TestDox("テンプレート変数がなくてもOK（DeveloperMessage）")]
    public function test_isNothingVariable_DeveloperMessage() {
        $template = "開発者向け: すべて正常です。";
        $message_template = new DeveloperMessagePromptTemplate($template);

        $input_variables = ["info", "level"];
        $test_result = $message_template->formatMessages($input_variables);
        $expected_obj[] = new DeveloperMessage("開発者向け: すべて正常です。");
        $this->assertEquals($expected_obj, $test_result);
    }
}
