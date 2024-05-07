<?php
namespace Clair\Ai\ChatAi\Prompt;

use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\SystemMessage;
use SebastianBergmann\CodeCoverage\Report\Text;

class SystemMessagePromptTemplate extends BaseTextMessagePromptTemplate
{

    public function formatMessages(array $arguments = []): array
    {
        $text_body = $this->assign($arguments);
        return [new SystemMessage($text_body)];
    }

}