<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\LLM\ChatLLM;
use Clair\Ai\ChatAi\Message\Content\Content;
use Clair\Ai\ChatAi\Message\Content\TextContent;

class SystemMessage implements Message
{
    public readonly Content $contents;

    public readonly ?string $name;
    private string $type = "system";


    public function __construct(
        string $contents,
        ?string $name = null
    ) {
        $this->contents = new TextContent($contents);
        $this->name = $name;
    }

    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function logFormat(): string
    {
        return "({$this->type}){$this->name}: {$this->contents->formatLog()}\n";
    }

    public function getType(): string
    {
        return $this->type;
    }
}