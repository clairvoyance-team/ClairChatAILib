<?php

namespace Clair\Ai\ChatAi\Message\Tool;

class ToolCall
{
    /**
     * @param string $name
     * @param array $args
     */
    public function __construct(
        public readonly string $name,
        public readonly array $args,
    ) {
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