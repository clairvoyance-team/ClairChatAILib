<?php
namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\Message\Message;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;

abstract class BaseTextMessagePromptTemplate extends BaseMessagePromptTemplate
{

    public readonly string $template;

    /**
     * @var string[] $input_variables テンプレート引数
     */
    public array $input_variables;

    /**
     * メモ用名前
     * @var string|null
     */
    public readonly ?string $name;

    public function __construct(
        string $template_str,
        ?string $name = null
    ) {
        $this->template = $template_str;
        $this->input_variables = $this->getTemplateVariables($template_str);
        $this->name = $name;
    }

    /**
     * @param array<string, mixed> $arguments テンプレート変数に入力する値 変数名: 入力値
     * @return Message[]
     *
     * @throws MissingInputVariablesException
     */
    abstract public function formatMessages(array $arguments = []): array;


    /**
     * テンプレートのテンプレート変数に入力値を代入する
     * @param array<string, mixed> $arguments 入力値 変数名: 入力値
     * @return string
     *
     * @throws MissingInputVariablesException
     */
    protected function assign(array $arguments = []): string
    {
        //入力値に含まれるテンプレート変数も取得
        foreach ($arguments as $argument) {
            $nested_input_variables = $this->getTemplateVariables($argument);
            $this->input_variables = array_unique(array_merge($this->input_variables, $nested_input_variables));
        }

        $assign_result = $this->template;
        foreach ($this->input_variables as $variable) {
            if (!array_key_exists($variable, $arguments)) throw new MissingInputVariablesException();

            $assign_result = preg_replace("/{{$variable}}/", $arguments[$variable], $assign_result);

            //入力値に含まれているテンプレート変数にも代入する
            foreach ($arguments as $key => $val) {
                $arguments[$key] = preg_replace("/{{$variable}}/", $arguments[$variable], $val);
            }
        }

        return $assign_result;
    }

}