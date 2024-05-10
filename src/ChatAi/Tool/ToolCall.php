<?php

namespace Clair\Ai\ChatAi\Tool;

interface ToolCall
{
    /**
     * ツールを実行
     * @param array<string, mixed> $input_arguments AIが生成した引数
     * @return mixed
     */
    public function run(array $input_arguments): mixed;

    /**
     * @return array{name: string, args: array}
     */
    public function toJsonArr(): array;
}