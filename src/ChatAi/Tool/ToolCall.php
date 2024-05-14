<?php

namespace Clair\Ai\ChatAi\Tool;

interface ToolCall
{
    /**
     * ツールを実行
     * @return mixed
     */
    public function run(): mixed;

    /**
     * @return array{name: string, args: array}
     */
    public function toJsonArr(): array;
}