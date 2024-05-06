<?php
namespace tests\Prompt;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Clair\Ai\ChatAi\Prompt\SystemMessagePromptTemplate;

class MessagePromptTest extends TestCase
{
    #[TestDox("正しいテンプレート変数の取得")]
    public function test_isCorrectVariable() {
        $template = "あなたは {input_language} を {output} に翻訳するアシスタントです。";
        $expected_variables = ["input_language", "output"];

        $message_template = new SystemMessagePromptTemplate($template);

        $this->assertEquals($expected_variables, $message_template->input_variables);
    }
}

