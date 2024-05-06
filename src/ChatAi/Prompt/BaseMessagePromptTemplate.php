<?php
namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\Message\Message;

abstract class BaseMessagePromptTemplate
{

    /**
     * @param array{string: mixed} $arguments テンプレート変数に入力する値 変数名: 入力値
     * @return Message[]
     */
    abstract public function formatMessages(array $arguments = []): array;


    /**
     * テンプレートからテンプレート変数を推定し、変数名を返す
     * @param string $template
     * @return string[] 推定されたテンプレート変数リスト
     */
    protected function getTemplateVariables(string $template): array
    {
        //スネークケースの変数のみ対応　例：{input_variable]
        preg_match_all('/{([a-z|_]+)}/', $template, $variables);
        return $variables[1]; //変数名だけを返す
    }

}