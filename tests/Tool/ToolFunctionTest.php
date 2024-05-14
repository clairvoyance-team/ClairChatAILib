<?php
namespace tests\Tool;

use Clair\Ai\ChatAi\Tool\Exception\InvalidFunctionException;
use Clair\Ai\ChatAi\Tool\JSONTypeEnum;
use Clair\Ai\ChatAi\Tool\ToolFunction;
use Clair\Ai\ChatAi\Tool\ToolFunctionParameter;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;


class ToolFunctionTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @throws ReflectionException
     * @throws InvalidFunctionException
     */
    #[TestDox("インスタンスからToolFunctionを作成できる")]
    public function test_canRecognize() {
        //testFunc
        //引数の説明がナシでもOK
        //引数のデフォルト値がなくてもOK

        $test_instance = new AIToolDefinedClass(123, 456);
        $expected = new ToolFunction(
            "testFunc",
            "メソッド自体の説明",
            [
                new ToolFunctionParameter("param", "パラメータ", true, JSONTypeEnum::Integer),
                new ToolFunctionParameter("str", "文字列", true, JSONTypeEnum::String),
                new ToolFunctionParameter("arr", null, true, JSONTypeEnum::Array),
                new ToolFunctionParameter("par2", "パラメータテストの2です", false, JSONTypeEnum::Integer),
            ],
            $test_instance
        );

        $tool_function = ToolFunction::readMethod($test_instance, "testFunc");
        $this->assertEquals($expected, $tool_function);
    }

    /**
     * @throws ReflectionException
     * @throws InvalidFunctionException
     */
    #[TestDox("staticメソッドでToolFunctionを作成できる")]
    public function test_canRecognizeStatic() {
        //testStaticFunc
        //PHPDocの引数の順序が逆でもOK　実装が優先
        //関数の説明なくてもOK
        //引数に型指定してなくてもPHPDocから認識可能
        //引数の型指定・PHPDocもなくてもOK

        $expected = new ToolFunction(
            "testStaticFunc",
            null,
            [
                new ToolFunctionParameter("param", null, true, JSONTypeEnum::Integer),
                new ToolFunctionParameter("str", "文字列", false, JSONTypeEnum::String),
                new ToolFunctionParameter("str2", null, false, JSONTypeEnum::String),
            ],
            "tests\Tool\AIToolDefinedClass"
        );

        $tool_function = ToolFunction::readMethod("tests\Tool\AIToolDefinedClass", "testStaticFunc");
        $this->assertEquals($expected, $tool_function);
    }

    /**
     * @throws ReflectionException
     */
    #[TestDox("引数がクラス文字列でstaticでないメソッドの場合は例外")]
    public function test_throwInvalidFunctionException() {
        $this->expectException(InvalidFunctionException::class);

        $test_instance = new AIToolDefinedClass(123, 456);
        ToolFunction::readMethod($test_instance, "testStaticFunc");
    }


    /**
     * @throws ReflectionException
     * @throws InvalidFunctionException
     */
    #[TestDox("インスタンスで関数実行できる")]
    public function test_canRunFunction()
    {
        //・必須でない引数はなしでも良い

        $test_instance = new AIToolDefinedClass(123, 456);
        $tool_function = ToolFunction::readMethod($test_instance, "testFunc");
        $args = [
            "param" => 1000,
            "str" => "文字列です",
            "arr" => ["0番目", "1番目", "2番目"]
        ];
        $result = $tool_function->run($args);

        $expected = "123::456::1000::文字列です::2番目::";

        $this->assertSame($expected, $result);
    }


    /**
     * @throws ReflectionException
     */
    #[TestDox("staticな関数実行できる")]
    public function test_canRunStaticFunction()
    {
        //・必須でない引数はなしでも良い

        $tool_function = ToolFunction::readMethod("tests\Tool\AIToolDefinedClass", "testStaticFunc");
        $args = [
            "param" => 1000,
            "str" => "文字列です",
            "str2" => "文字列です222"
        ];
        $result = $tool_function->run($args);

        $expected = "1000::文字列です::文字列です222";

        $this->assertSame($expected, $result);
    }

    /**
     * @throws ReflectionException
     * @throws InvalidFunctionException
     */
    #[TestDox("引数なし関数を読み込み・実行できる")]
    public function test_functionWithoutArgs()
    {
        //・必須でない引数はなしでも良い

        $tool_function = ToolFunction::readMethod("tests\Tool\AIToolDefinedClass", "noArgs");
        $result = $tool_function->run(null);

        $expected = "calling without Args";

        $this->assertSame($expected, $result);
    }

    /**
     * @throws ReflectionException
     */
    #[TestDox("JSON配列に変換できる")]
    public function test_canConvertToJson() {
        $test_instance = new AIToolDefinedClass(123, 456);
        $example1 = new ToolFunction(
            "testFunc",
            "メソッド自体の説明",
            [
                new ToolFunctionParameter("param", "パラメータ", true, JSONTypeEnum::Integer),
                new ToolFunctionParameter("str", "文字列", true, JSONTypeEnum::String),
                new ToolFunctionParameter("arr", null, true, JSONTypeEnum::Array),
                new ToolFunctionParameter("par2", "パラメータテストの2です", false, JSONTypeEnum::Integer),
            ],
            $test_instance
        );

        $json1 = $example1->toRequestArr();
        $expected1 = [
            "type" => "function",
            "function" => [
                "name" => "testFunc",
                "description" => "メソッド自体の説明",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "param" => [
                            "type" => "integer",
                            "description" => "パラメータ"
                        ],
                        "str" => [
                            "type" => "string",
                            "description" => "文字列"
                        ],
                        "arr" => [
                            "type" => "array"
                        ],
                        "par2" => [
                            "type" => "integer",
                            "description" => "パラメータテストの2です"
                        ]
                    ],
                    "required" => [
                        "param",
                        "str",
                        "arr"
                    ]
                ]
            ]
        ];

        $example2 = new ToolFunction(
            "testStaticFunc",
            null,
            [
                new ToolFunctionParameter("param", null, true, JSONTypeEnum::Integer)
            ],
            "tests\Tool\AIToolDefinedClass"
        );
        $json2 = $example2->toRequestArr();

        $expected2 = [
            "type" => "function",
            "function" => [
                "name" => "testStaticFunc",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "param" => [
                            "type" => "integer"
                        ]
                    ],
                    "required" => [
                        "param"
                    ]
                ]
            ]
        ];

        $this->assertSame($expected1, $json1);
        $this->assertSame($expected2, $json2);
    }


}


class AIToolDefinedClass {

    public function __construct(
        public readonly int $users_id,
        public readonly  int $character_id
    ) {}

    /**
     * メソッド自体の説明
     * @param int $param パラメータ
     * @param string $str 文字列
     * @param array $arr
     * @param int|null $par2 パラメータテストの2です
     * @return string
     */
    public function testFunc(int $param, string $str, array $arr, int $par2=null) :string
    {
        return "$this->users_id::$this->character_id::$param::$str::{$arr[2]}::$par2";
    }

    /**
     * @param string|null $str 文字列
     * @param int $param
     * @return string
     */
    public static function testStaticFunc($param, string $str=null, $str2=null) :string
    {
        return "$param::$str::$str2";
    }

    public static function noArgs() :string
    {
        return "calling without Args";
    }
}