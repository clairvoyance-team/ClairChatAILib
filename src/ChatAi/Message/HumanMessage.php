<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\LLM\ChatLLM;
use Clair\Ai\ChatAi\Message\Content\Content;
use Clair\Ai\ChatAi\Message\Content\ImageContent;
use Clair\Ai\ChatAi\Message\Content\TextContent;

class HumanMessage implements Message
{
    public readonly array $contents;

    public readonly ?string $name;

    private string $type = "human";

    /**
     * @param string|Content[] $contents デフォルトはstringという意味
     * @param string|null $name
     */
    public function __construct(
        string|array $contents,
        ?string      $name = null
    ) {
        if (is_string($contents)) {
            $this->contents = [ new TextContent($contents) ];
        } else {
            $this->contents = $contents;
        }

        $this->name = $name;
    }

    /**
     * ログ用に文字列で表すフォーマットを設定する
     * @return string
     */
    public function logFormat(): string
    {
        $contents_format_arr = array_map(fn($val) :string => $val->formatLog(), $this->contents);
        $content = implode("\n", $contents_format_arr);

        return "({$this->type}){$this->name}: {$content}\n";
    }

    public function getType(): string
    {
        return $this->type;
    }
}