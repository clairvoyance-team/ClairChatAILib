<?php
namespace tests\Tool;

use Clair\Ai\ChatAi\Tool\Exception\InvalidFunction;
use Clair\Ai\ChatAi\Tool\JSONTypeEnum;
use Clair\Ai\ChatAi\Tool\ToolFunction;
use Clair\Ai\ChatAi\Tool\ToolParameter;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use ReflectionException;


class ToolFunctionTest extends TestCase
{

    /**
     * @throws ReflectionException
     * @throws InvalidFunction
     */
    #[TestDox("インスタンスからToolFunctionを作成できる")]
    public function test_canRecognize() {
        $test_instance = new AIToolDefinedClass(123, 456);
        $expected = new ToolFunction(
            "testFunc",
            "メソッド自体の説明",
            [
                new ToolParameter("param", "パラメータ", true, JSONTypeEnum::Integer),
                new ToolParameter("str", "文字列", true, JSONTypeEnum::String),
                new ToolParameter("arr", null, true, JSONTypeEnum::Array),
                new ToolParameter("par2", "パラメータテストの2です", false, JSONTypeEnum::Integer),
            ]
        );

        $tool_function = ToolFunction::readMethod($test_instance, "testFunc");
        $this->assertEquals($expected, $tool_function);
    }

    /**
     * @throws ReflectionException
     * @throws InvalidFunction
     */
    #[TestDox("staticメソッドでToolFunctionを作成できる")]
    public function test_canRecognizeStatic() {
        $expected = new ToolFunction(
            "testStaticFunc",
            null,
            [
                new ToolParameter("param", null, true, JSONTypeEnum::Integer),
                new ToolParameter("str", "文字列", false, JSONTypeEnum::String),
            ]
        );

        $tool_function = ToolFunction::readMethod("tests\Tool\AIToolDefinedClass", "testStaticFunc");
        $this->assertEquals($expected, $tool_function);
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
     * @param int $param
     * @param string|null $str 文字列
     * @return string
     */
    static public function testStaticFunc(int $param, string $str=null) :string
    {
        return "$param::$str";
    }
}