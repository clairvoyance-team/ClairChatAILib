<?php

namespace Clair\Ai\ChatAi\Message;

use Clair\Ai\ChatAi\LLM\ChatLLM;
use Clair\Ai\ChatAi\Message\Content\Content;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;

class AIMessage implements Message
{

    /**
     * @var Content[]
     */
    public readonly array $contents;

    public readonly ?string $name;
    private string $type = "ai";

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

    /**
     * テキストの内容があればそれを返す
     * @return string|null
     */
    public function getTextContents(): ?string
    {
        foreach ($this->contents as $content) {
            if ($content instanceof TextContent) {
                return $content->getContents();
            }
        }

        return null;
    }

    /**
     * @return ToolCallingContent[]
     */
    public function getToolCalling(): array
    {
        $tool_content_arr = [];
        foreach ($this->contents as $content) {
            if ($content instanceof ToolCallingContent) {
                $tool_content_arr[] = $content;
            }
        }

        return $tool_content_arr;
    }
}