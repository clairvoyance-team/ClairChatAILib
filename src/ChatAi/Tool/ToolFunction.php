<?php

namespace Clair\Ai\ChatAi\Tool;

use Clair\Ai\ChatAi\Tool\Exception\InvalidFunctionException;
use Clair\Ai\ChatAi\Tool\Exception\MissingExecutorException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class ToolFunction extends Tool
{
    public const ToolType = ToolType::Function;

    /**
     * @param string $name
     * @param string|null $description
     * @param ToolFunctionParameter[]|null $parameters
     * @param object|string|null $instance_or_class 実行時用のインスタンスもしくはクラス名
     * @throws ReflectionException
     * @throws InvalidFunctionException
     */
    public function __construct(
        public readonly string     $name,
        public readonly ?string    $description = null,
        public readonly ?array     $parameters = null,
        private readonly object|string|null $instance_or_class = null
    )
    {
        if (!is_null($this->instance_or_class)) {
            $reflector = new ReflectionClass($instance_or_class);
            $method = $reflector->getMethod($name);

            if ($method->isProtected() || $method->isPrivate()) {
                throw new InvalidFunctionException("publicメソッドの関数にしてください");
            }

            if (is_object($instance_or_class) && $method->isStatic()) {
                throw new InvalidFunctionException("インスタンスを渡した場合はstaticでない関数である必要があります");
            }

            if (is_string($instance_or_class) && !$method->isStatic()) {
                throw new InvalidFunctionException("インスタンスを渡さない場合はstatic関数である必要があります");
            }
        }
    }

    public function getType(): ToolType
    {
        return self::ToolType;
    }


    /**
     * 配列から作る
     * @param string $name
     * @param string|null $description
     * @param array|null $parameters_arr
     * @return ToolFunction
     * @throws ReflectionException
     * @throws InvalidFunctionException
     */
    public static function fromArray(string $name, string $description=null, array $parameters_arr=null): ToolFunction
    {
        $parameters = [];
        if (!is_null($parameters_arr)) {
            foreach ($parameters_arr as $parameter) {
                $parameters[] = ToolFunctionParameter::fromArray($parameter);
            }
        }

        return new self($name, $description, $parameters);
    }

    /**
     * @param $class object|string クラス名の文字列か、クラスのインスタンスを渡す
     * @param $method_name string
     * @return ToolFunction
     * @throws ReflectionException
     * @throws InvalidFunctionException
     */
    public static function readMethod(object|string $class, string $method_name): ToolFunction
    {
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod($method_name);

        $php_doc = $method->getDocComment();
        $doc_contents = ToolFunction::getContentsPHPDoc($php_doc);

        $method_parameters = $method->getParameters();
        $doc_parameters = array_map(fn($value) => ToolFunction::analyzePHPDocParam($value), $doc_contents["params"]);

        $tool_parameters = self::generateToolParameters($method_parameters, $doc_parameters);

        $func_name = $method->getName();
        $func_description = $doc_contents["func_description"] ?? null;

        return new self($func_name, $func_description, $tool_parameters, $class);
    }

    /**
     * @param string $php_doc
     * @return array{func_description: string, params: array}
     */
    private static  function getContentsPHPDoc(string $php_doc): array
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
    private static function analyzePHPDocParam(string $param_str): array
    {
        $param_arr = explode(" ", $param_str);

        //指定してない時に順序が変わるものがある
        if (count($param_arr) == 2) {
            //@param $arg
            return ["name" => trim($param_arr[1], "$"), "type" => null, "description" => null];
        } else {
            //@param string $arg 説明
            return ["name" => trim($param_arr[2], "$"), "type" => $param_arr[1], "description" => $param_arr[3] ?? null];
        }
    }

    /**
     * @param ReflectionParameter[] $reflection_parameters
     * @param array{name: string, type: string, description: string}[] $doc_parameters
     * @return array
     */
    private static function generateToolParameters(array $reflection_parameters, array $doc_parameters): array
    {
        $tool_parameters = [];
        foreach ($reflection_parameters as $method_param) {
            $param_name = $method_param->getName();

            //PHPDocに書かれている該当の引数を探す
            $key = array_search($param_name, array_column($doc_parameters, 'name'));
            $doc_parameter = ($key !== false) ? $doc_parameters[$key] : null;

            $type = ($method_param->hasType()) ? $method_param->getType() : ($doc_parameter["type"] ?? "");
            //認識可能な型
            $allow_type = "/int|string|array|float|bool|object/";
            $type = (preg_match($allow_type, $type, $match)) ? $match[0] : null;
            $json_type = ($type) ? JSONTypeEnum::from($type) : JSONTypeEnum::String;

            //デフォルト値が無ければ必須ということ
            $required = !$method_param->isDefaultValueAvailable();

            $tool_parameters[] = new ToolFunctionParameter(
                $param_name,
                $doc_parameter["description"] ?? null,
                $required,
                $json_type
            );
        }

        return $tool_parameters;
    }

    /**
     * 自身をに変換する
     * @return array
     */
    public function toRequestArr() :array
    {
        $function_request_arr["name"] = $this->name;

        if (!is_null($this->description)) {
            $function_request_arr["description"] = $this->description;
        }

        //引数の部分
        if (!is_null($this->parameters)) {
            $required_parameter_arr = [];
            $parameters_request_arr = [];
            foreach ($this->parameters as $parameter) {
                $parameters_request_arr = array_merge($parameters_request_arr, $parameter->convertToJsonArr());

                if ($parameter->required) {
                    $required_parameter_arr[] = $parameter->name;
                }
            }

            $function_request_arr["parameters"] = [
                "type" => "object",
                "properties" => $parameters_request_arr,
                "required" => $required_parameter_arr
            ];
        }

        return [
            "type" => "function",
            "function" => $function_request_arr
        ];
    }

    /**
     * AIが生成した引数を元に、関数を実行する
     * @param array<string, mixed>|null $argument AIが生成した引数
     * @return mixed ToolFunctionに渡された関数によって異なる
     * @throws ReflectionException
     */
    public function run(array|null $argument) :mixed
    {
        if (is_null($this->instance_or_class)) throw new MissingExecutorException("関数を実行するインスタンスもしくはクラスが登録されていません");

        $reflector = new ReflectionClass($this->instance_or_class);
        $parameters = $reflector->getMethod($this->name)->getParameters();
        $parameter_name_arr = array_map(fn($value) :string => $value->getName(), $parameters);

        //一応実際の関数実装の引数順に並び替えて格納
        $func_argument = [];
        foreach ($parameter_name_arr as $parameter_name) {
            if (isset($argument[$parameter_name])) {
                $func_argument[] = $argument[$parameter_name];
            }
        }

        if (is_string($this->instance_or_class)) {
            //staticメソッド
            $class_name = $this->instance_or_class;
            $result = call_user_func_array([$class_name, $this->name], $func_argument);
        } else {
            //インスタンスからのメソッド
            $instance = $this->instance_or_class;
            $result = $instance->{$this->name}(...$func_argument);
        }

        return $result;
    }

    public function getToolCallInstance(array|null $input_arguments): ToolFunctionCall
    {
        return new ToolFunctionCall($this->name, $input_arguments, $this);
    }
}