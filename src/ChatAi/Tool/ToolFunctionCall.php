<?php

namespace Clair\Ai\ChatAi\Tool;

use ReflectionException;

class ToolFunctionCall implements ToolCall
{

    /**
     * @param string $name
     * @param ?array<string, mixed> $input_arguments <引数名, 値>
     * @param ToolFunction $function
     */
    public function __construct(
        public readonly string       $name,
        public readonly ?array       $input_arguments,
        public readonly ToolFunction $function
    ) {
    }

    /**
     * @return mixed
     * @throws ReflectionException
     */
    public function run(): mixed
    {
        return $this->function->run($this->input_arguments);
    }

    /**
     * @return array{name: string, args: array}
     */
    public function toJsonArr(): array
    {
        return ["name" => $this->name, "args" => $this->input_arguments];
    }

    public function logFormat(): string
    {
        $args_str_arr = [];
        foreach ($this->input_arguments as $arg_name => $arg_value) {
            $args_str_arr[] = "{$arg_name}={$arg_value}";
        }

        return $this->name . "(" . implode(', ', $args_str_arr) . ")";
    }


}