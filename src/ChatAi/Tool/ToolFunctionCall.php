<?php

namespace Clair\Ai\ChatAi\Tool;

use ReflectionException;

class ToolFunctionCall implements ToolCall
{

    /**
     * @param string $name
     * @param array $args
     * @param ToolFunction $function
     */
    public function __construct(
        public readonly string $name,
        public readonly array $args,
        public readonly ToolFunction $function
    ) {
    }

    /**
     * @param array<string, mixed> $input_arguments AIが生成した引数
     * @return mixed
     * @throws ReflectionException
     */
    public function run(array $input_arguments): mixed
    {
        return $this->function->run($input_arguments);
    }

    /**
     * @return array{name: string, args: array}
     */
    public function toJsonArr(): array
    {
        return ["name" => $this->name, "args" => $this->args];
    }

    public function logFormat(): string
    {
        $args_str_arr = [];
        foreach ($this->args as $arg_name => $arg_value) {
            $args_str_arr[] = "{$arg_name}={$arg_value}";
        }

        return $this->name . "(" . implode(', ', $args_str_arr) . ")";
    }


}