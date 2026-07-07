<?php

namespace tests\LLM;

use Clair\Ai\ChatAi\LLM\Grok\GrokResult;
use Clair\Ai\ChatAi\Tool\JSONTypeEnum;
use Clair\Ai\ChatAi\Tool\ToolFunction;
use Clair\Ai\ChatAi\Tool\ToolFunctionCall;
use Clair\Ai\ChatAi\Tool\ToolFunctionParameter;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class GrokResultTest extends TestCase
{
    #[TestDox('Grokレスポンスのテキスト選択肢を変換できる')]
    public function test_canCreateTextChoice(): void
    {
        $response = json_decode(json_encode([
            'id' => 'chatcmpl-grok-1',
            'model' => 'grok-3-mini',
            'created' => 1720000000,
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hi, there!',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15,
            ],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $result = new GrokResult($response, null);
        $choices = $result->getChoices();

        $this->assertSame('grok-3-mini', $result->getModelName());
        $this->assertSame('Hi, there!', $choices[0]->getContents());
        $this->assertSame(['input_tokens' => 10, 'output_tokens' => 5], $result->getUsage());
    }

    #[TestDox('Grokレスポンスのtool_calls選択肢を変換できる')]
    public function test_canCreateToolChoice(): void
    {
        $tool1 = new ToolFunction(
            'get_current_weather',
            'メソッド自体の説明',
            [
                new ToolFunctionParameter('location', '地域', true, JSONTypeEnum::String),
            ],
            null
        );
        $tool2 = new ToolFunction(
            'get_current_temperature',
            'メソッド自体の説明',
            [
                new ToolFunctionParameter('location', '地域', true, JSONTypeEnum::String),
            ],
            null
        );

        $response = json_decode(json_encode([
            'id' => 'chatcmpl-grok-2',
            'model' => 'grok-3-mini',
            'created' => 1720000000,
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            [
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_current_weather',
                                    'arguments' => '{"location":"Tokyo"}',
                                ],
                            ],
                            [
                                'id' => 'call_2',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_current_temperature',
                                    'arguments' => '{"location":"Tokyo"}',
                                ],
                            ],
                        ],
                    ],
                    'finish_reason' => 'tool_calls',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 30,
                'completion_tokens' => 12,
                'total_tokens' => 42,
            ],
        ], JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);

        $result = new GrokResult($response, [$tool1, $tool2]);
        $tools = $result->getChoices()[0]->getTools();

        $this->assertCount(2, $tools);
        $this->assertInstanceOf(ToolFunctionCall::class, $tools[0]->tool_call);
        $this->assertSame('call_1', $tools[0]->tool_call_id);
        $this->assertSame('get_current_weather', $tools[0]->tool_call->name);
        $this->assertSame('Tokyo', $tools[0]->tool_call->input_arguments['location']);
    }
}

