<?php
namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\Message\Message;

abstract class BaseMessagePromptTemplate
{

    /**
     * テンプレートからテンプレート変数を推定し、変数名を返す
     * @param string $template
     * @return string[] 推定されたテンプレート変数リスト
     */
    protected function getTemplateVariables(string $template): array
    {
        //スネークケースの変数のみ対応　例：{input_variable}
        preg_match_all('/{([a-z|_]+)}/', $template, $variables);
        return $variables[1]; //変数名だけを返す
    }

    /**
     * テンプレートのテンプレート変数に入力値を代入する
     * @param array{string: mixed} $arguments 入力値 変数名: 入力値
     * @return string
     */
    abstract protected function assign(array $arguments = []): string;

    /**
     * テンプレートのテンプレート変数に入力値を代入し、Message型で返す
     * @param array{string: mixed} $arguments 入力値 変数名: 入力値
     * @return Message[]
     */
    abstract public function formatMessages(array $arguments = []): array;


}