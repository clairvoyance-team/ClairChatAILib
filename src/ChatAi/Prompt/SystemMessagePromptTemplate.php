<?php
namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\Message\SystemMessage;

class SystemMessagePromptTemplate extends BaseMessagePromptTemplate
{

    public readonly string $template;

    /**
     * @var string[] $input_variables テンプレート引数
     */
    public readonly array $input_variables;

    public function __construct(
        string $template_str
    ) {
        $this->template = $template_str;
        $this->input_variables = $this->getTemplateVariables($template_str);
    }

    public function formatMessages(array $arguments = []): array
    {
        return [];
    }

}