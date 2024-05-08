<?php

namespace Clair\Ai\ChatAi\Tool;

use ReflectionClass;
use ReflectionException;

class ToolFunction
{

    public const ToolType = ToolType::Function;

    public readonly string $name;

    public readonly string $description;

    /**
     * @var ToolParameter[]
     */
    public readonly ?array $parameters;


    /**
     * @param string $name
     * @param string $description
     * @param array|null $parameters_arr
     */
    public function __construct(string $name, string $description, array $parameters_arr=null)
    {
        $this->name = $name;
        $this->description = $description;

        if (!is_null($parameters_arr)) {
            foreach ($parameters_arr as $parameter) {
                $this->parameters[] = new ToolParameter($parameter);
            }
        }
    }

    /**
     * @param $class_name
     * @param $method_name
     * @return void
     * @throws ReflectionException
     */
    static public function readMethod($class_name, $method_name) {
        $reflector = new ReflectionClass($class_name);
        $method = $reflector->getMethod($method_name);

        $php_doc = $method->getDocComment();
    }
}