<?php
namespace tests\LLM;

use Clair\Ai\ChatAi\LLM\OpenAIResponseChoice;
use Clair\Ai\ChatAi\LLM\OpenAIResult;
use Clair\Ai\ChatAi\Message\AIMessage;

use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Tool\JSONTypeEnum;
use Clair\Ai\ChatAi\Tool\ToolFunction;
use Clair\Ai\ChatAi\Tool\ToolFunctionCall;
use Clair\Ai\ChatAi\Tool\ToolFunctionParameter;
use Clair\Ai\ChatAi\Tool\ToolType;
use OpenAI\Responses\Chat\CreateResponse;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class OpenAIResultTest extends TestCase
{
    #[TestDox("生成テキストの提案が1つの場合にAIMessageを含むレスObjを生成できる")]
    public function test_canCreateTextChoice() {
        $response = CreateResponse::fake([
            'id' => 'chatcmpl-111',
            'choices' => [
                [
                    'message' => [
                        'content' => 'Hi, there!',
                    ],
                ],
            ],
            'system_fingerprint' => 'fp_44709d6fcb',
        ]);

        $result = new OpenAIResult($response, null);

        $expected_choices = [
            new OpenAIResponseChoice(0, new AIMessage('Hi, there!'), "stop")
        ];

        $this->assertEquals($expected_choices, $result->choices);
    }

    #[TestDox("生成テキストの提案が2つの場合にAIMessageを含むレスObjを生成できる")]
    public function test_canCreateText2Choices() {
        $response = CreateResponse::fake([
            'id' => 'chatcmpl-112',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role'    => 'assistant',
                        'content' => 'Hi, there!',
                    ],
                    'logprobs' => null,
                    'finish_reason' => 'stop',
                ],
                [

                    'index' => 1,
                    'message' => [
                        'role'    => 'assistant',
                        'content' => 'こんにちは！',
                    ],
                    'logprobs' => null,
                    'finish_reason' => 'stop',
                ]
            ],
            'system_fingerprint' => 'fp_44709d6fcb',
        ]);

        $result = new OpenAIResult($response, null);

        $expected_choices = [
            new OpenAIResponseChoice(0, new AIMessage('Hi, there!'), "stop"),
            new OpenAIResponseChoice(1, new AIMessage('こんにちは！'), "stop"),
        ];

        $this->assertEquals($expected_choices, $result->choices);
    }

    /**
     * @throws \ReflectionException
     */
    #[TestDox("Tool提案2つの場合にAIMessageを含むレスObjを生成できる")]
    public function test_canCreateToolChoice() {
        $tool1 = new ToolFunction(
            "get_current_weather",
            "メソッド自体の説明",
            [
                new ToolFunctionParameter("location", "地域", true, JSONTypeEnum::String),
            ],
            null
        );
        $tool2 = new ToolFunction(
            "get_current_temperature",
            "メソッド自体の説明",
            [
                new ToolFunctionParameter("location", "地域", true, JSONTypeEnum::String),
            ],
            null
        );

        $response = CreateResponse::fake([
            'id' => 'chatcmpl-111',
            'choices' => [
                [
                    'message' => [
                        'tool_calls' => [
                            [
                                'id' => 'call_trlgKnhMpYSC7CFXKw3CceUZ',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_current_weather',
                                    'arguments' => "{\n  \"location\": \"Tokyo\"\n}",
                                ],
                            ],
                            [
                                'id' => 'call_trlgKnhMpYSC7CFXKw3CceAA',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_current_temperature',
                                    'arguments' => "{\n  \"location\": \"Tokyo\"\n}",
                                ],
                            ],
                        ],
                    ],
                    'finish_reason' => 'tool_calls',
                ],
            ],
        ]);

        $result = new OpenAIResult($response, [$tool1, $tool2]);

        $expected_choices = [
            new OpenAIResponseChoice(
                0,
                new AIMessage([
                    new ToolCallingContent(
                        "call_trlgKnhMpYSC7CFXKw3CceUZ",
                        ToolType::Function,
                        new ToolFunctionCall("get_current_weather", ["location" => "Tokyo"], $tool1),
                    ),
                    new ToolCallingContent(
                        "call_trlgKnhMpYSC7CFXKw3CceAA",
                        ToolType::Function,
                        new ToolFunctionCall("get_current_temperature", ["location" => "Tokyo"], $tool2),
                    ),
                ]),
                "tool_calls"
            ),
        ];

        $this->assertEquals($expected_choices, $result->choices);
    }
}