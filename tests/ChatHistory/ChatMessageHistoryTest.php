<?php

namespace tests\ChatHistory;

use Clair\Ai\ChatAi\ChatHistory\ChatMessageHistory;

use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\HumanMessage;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class ChatMessageHistoryTest extends TestCase
{

    #[TestDox("コンストラクタ引数はMessageしか受け付けない")]
    public function test_throwExceptionWhenArgumentIsInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        new ChatMessageHistory(["message", "message"]);
    }

    #[TestDox("任意のメッセージを追加できる")]
    public function test_canAddHumanMessage()
    {
        $chat_history = new ChatMessageHistory([new HumanMessage("テスト")]);
        $chat_history->addAIMessage("AIだよ");
        $chat_history->addUserMessage("ユーザだよ");

        $test_arr = $chat_history->toArray();
        $this->assertInstanceOf(HumanMessage::class, $test_arr[0]);
        $this->assertEquals(new TextContent("テスト"), $test_arr[0]->content);

        $this->assertInstanceOf(AIMessage::class, $test_arr[1]);
        $this->assertEquals(new TextContent("AIだよ"), $test_arr[1]->content);

        $this->assertInstanceOf(HumanMessage::class, $test_arr[2]);
        $this->assertEquals(new TextContent("ユーザだよ"), $test_arr[2]->content);
    }
}