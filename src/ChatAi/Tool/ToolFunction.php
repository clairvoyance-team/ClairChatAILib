<?php

namespace Clair\Ai\ChatAi\Tool;

use Clair\Ai\ChatAi\Tool\Exception\InvalidFunction;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class ToolFunction
{
    public const ToolType = ToolType::Function;

    /**
     * @param string $name
     * @param string|null $description
     * @param ToolParameter[]|null $parameters
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description=null,
        public readonly ?array $parameters=null
    ){}


    /**
     * 配列から作る
     * @param string $name
     * @param string|null $description
     * @param array|null $parameters_arr
     * @return ToolFunction
     */
    static function fromArray(string $name, string $description=null, array $parameters_arr=null): ToolFunction
    {
        $parameters = [];
        if (!is_null($parameters_arr)) {
            foreach ($parameters_arr as $parameter) {
                $parameters[] = ToolParameter::fromArray($parameter);
            }
        }

        return new self($name, $description, $parameters);
    }

    /**
     * @param $class object|string クラス名の文字列か、クラスのインスタンスを渡す
     * @param $method_name string
     * @return ToolFunction
     * @throws ReflectionException
     * @throws InvalidFunction
     */
    static public function readMethod(object|string $class, string $method_name): ToolFunction
    {
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod($method_name);
        print_r($method);

        if ($method->isProtected() || $method->isPrivate()) {
            throw new InvalidFunction("publicメソッドの関数にしてください");
        }

        if (is_object($class) && $method->isStatic()) {
            throw new InvalidFunction("インスタンスを渡した場合はstaticでない関数である必要があります");
        }

        if (is_string($class) && !$method->isStatic()) {
            throw new InvalidFunction("インスタンスを渡さない場合はstatic関数である必要があります");
        }

        $php_doc = $method->getDocComment();
        $doc_contents = ToolFunction::getContentsPHPDoc($php_doc);

        $method_parameters = $method->getParameters();
        $doc_parameters = array_map(fn($value) => ToolFunction::analyzePHPDocParam($value), $doc_contents["params"]);

        $tool_parameters = self::generateToolParameters($method_parameters, $doc_parameters);

        $func_name = $method->getName();
        $func_description = $doc_contents["func_description"] ?? null;

        return new self($func_name, $func_description, $tool_parameters);
    }

    /**
     * @param string $php_doc
     * @return array{func_description: string, params: array}
     */
    static private function getContentsPHPDoc(string $php_doc): array
    {
        //PHPDocの 「* 」と空白を除く
        $document_trim = array_map(fn($val) :string => trim(trim($val), "* "), explode("\n", $php_doc));
        //PHPDocの開始と終了を取る
        $document_contents =  array_slice($document_trim, 1, -1);

        $params = [];
        $description = [];
        foreach ($document_contents as $line) {
            if (!str_starts_with($line, "@")) {
                $description[] = $line;
            }

            if (str_starts_with($line, "@param")) {
                $params[] = $line;
            }
        }

        return ["func_description" => implode("\n", $description) ?: null, "params" => $params];
    }

    /**
     * @param string $param_str
     * @return array{name: string, type: string, description: string}
     */
    static private function analyzePHPDocParam(string $param_str): array
    {
        $param_arr = explode(" ", $param_str);

        //指定してない時に順序が変わるものがある
        if (count($param_arr) == 2) {
            //@param $arg
            return ["name" => trim($param_arr[1], "$"), "type" => null, "description" => null];
        } else {
            //@param string $arg 説明
            return ["name" => trim($param_arr[2], "$"), "type" => $param_arr[1], "description" => $param_arr[3]];
        }
    }

    /**
     * @param ReflectionParameter[] $reflection_parameters
     * @param array{name: string, type: string, description: string}[] $doc_parameters
     * @return array
     */
    static private function generateToolParameters(array $reflection_parameters, array $doc_parameters): array
    {
        print_r($doc_parameters);
        $tool_parameters = [];
        foreach ($reflection_parameters as $method_param) {
            $param_name = $method_param->getName();

            //PHPDocに書かれている該当の引数を探す
            $key = array_search($param_name, array_column($doc_parameters, 'name'));
            $doc_parameter = ($key !== false) ? $doc_parameters[$key] : null;

            $type = ($method_param->hasType()) ? $method_param->getType() : $doc_parameter["type"];
            //認識可能な型
            $allow_type = "/int|string|array|float/";
            $type = (preg_match($allow_type, $type, $match)) ? $match[0] : null;
            $json_type = ($type) ? JSONTypeEnum::from($type) : JSONTypeEnum::String;

            //デフォルト値が無ければ必須ということ
            $required = !$method_param->isDefaultValueAvailable();

            $tool_parameters[] = new ToolParameter(
                $param_name,
                $doc_parameter["description"] ?: null,
                $required,
                $json_type
            );
        }

        return $tool_parameters;
    }
}