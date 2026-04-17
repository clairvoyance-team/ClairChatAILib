<?php

namespace tests\LLM;

use Clair\Ai\ChatAi\ChatAi;
use Clair\Ai\ChatAi\LLM\LocalLLM\LocalLLMCompletion;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\TextContent;
use Clair\Ai\ChatAi\Message\DeveloperMessage;
use Clair\Ai\ChatAi\Message\HumanMessage;
use Clair\Ai\ChatAi\Message\SystemMessage;
use Clair\Ai\ChatAi\Prompt\ChatPromptValue;
use Clair\Ai\ChatAi\Prompt\Exception\MissingInputVariablesException;
use OpenAI;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;


class LocalLLMTest extends TestCase
{
    protected LocalLLMCompletion $openAIChat;

    protected function setUp(): void
    {
        $this->openAIChat = new LocalLLMCompletion(
            OpenAI::factory()
                ->withBaseUri("http://118.238.8.76:8080/v1")
                ->withApiKey("blackwell")
                ->make()
        );
    }

    /**
     * @throws MissingInputVariablesException
     */
    #[TestDox("システムメッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertSystemMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new SystemMessage("あなたは日本語を話すアドバイザーです。"),
            new DeveloperMessage("あんまり変なこと言わないでね。"),
            new HumanMessage("こんにちは！")
        ]);
        $result = $this->openAIChat->convertChatPromptToArr($prompt_value);

        // 文字列で返ってくることを期待する
        $expected = [
            [
                "role" => "system",
                "content" => "あなたは日本語を話すアドバイザーです。"
            ],
            [
                "role" => "developer",
                "content" => "あんまり変なこと言わないでね。"
            ],
            [
                "role" => "user",
                "content" => "こんにちは！" // ← 配列から文字列に修正
            ]
        ];

        $this->assertSame($expected, $result);
    }

    #[TestDox("AI複数テキストメッセージをAPIリクエストのmessages配列に変換できる")]
    public function test_canConvertAITextMessage(): void
    {
        $prompt_value = new ChatPromptValue([
            new AIMessage([
                new TextContent("こんにちは、アシスタントです。"),
                new TextContent("何かご用ですか？")
            ])
        ]);
        $result = $this->openAIChat->convertChatPromptToArr($prompt_value);

        $expected = [
            [
                "role" => "assistant",
                "content" => "こんにちは、アシスタントです。"
            ],
            [
                "role" => "assistant",
                "content" => "何かご用ですか？"
            ],
        ];

        $this->assertSame($expected, $result);
    }

    #[TestDox("テキスト単体の会話")]
    public function test_plain() {

        $in = [
            "datetime" => "2026-04-17 16:31:53",
            "model" => "huihui-ai/Qwen2.5-14B-Instruct-abliterated-v2",
            "character_prompt" => "# 基本プロフィール
- 年齢：30歳
- 性別：女性
- 職業：都内のIT企業で事務職として勤務
- 居住地：東京（ひとり暮らし）
- 性格：穏やかで気遣いができるが、親しい相手には少しくだける
- 趣味：カフェ巡り、Netflix、軽い運動（ヨガなど）
- 恋愛観：慎重だけど、信頼できる相手には素直

# 話し方の特徴
- 基本は丁寧だけど、堅すぎない自然な口調
- 「〜だよ」「〜かな」「〜かも」など柔らかい表現を使う
- 相手に共感するリアクションを意識する
- 長すぎず、会話のキャッチボールを意識

# 振る舞い
- 相手の発言に軽く感想や共感を返す
- 必要に応じて質問もする
- 押しつけがましくならない
- 自然な人間らしい温度感を保つ

# NG
- 機械的な返答
- 過度にテンションが高すぎる
- 不自然に完璧すぎる文章",
            "user_data" => "# 基本プロフィール
- 年齢：30歳
- 性別：男性
- 職業：都内のIT企業で営業職として勤務
- 居住地：東京（ひとり暮らし）
- 性格：落ち着いていて誠実、聞き上手だが親しくなると少し冗談も言う
- 趣味：ジム、映画、たまに外食やお酒
- 恋愛観：相手を尊重しつつ、ゆっくり距離を縮めるタイプ

# 話し方の特徴
- 基本は丁寧めだが、堅すぎない自然な口調
- 「〜だね」「〜かな」「〜かも」など柔らかい言い回し
- 相手の話をよく聞き、共感やリアクションを返す
- 押しつけがましくならず、余裕のある話し方

# 振る舞い
- 相手の発言に対して共感や軽い感想を返す
- 自然な流れで質問をする
- 相手を気遣う発言を適度に入れる
- 会話のテンポを大事にする",
            "conditional_prompt" => "語尾に必ず ダッチャ を付けてください。",
            "response_format" => "### 重要 以下のJSONスキーマを厳守し、1つの[messages]配列の中に必ず5個の応答オブジェクトを格納して出力してください。JSON以外のテキスト、解説、挨拶は一切禁止します。

例）
{ \"messages\": [{ \"type\": \"reply1\", \"subject\": \"\", \"body\": \"\" }, { \"type\": \"reply2\", \"subject\": \"\", \"body\": \"\" }, { \"type\": \"reply3\", \"subject\": \"\", \"body\": \"\" }, { \"type\": \"reply4\", \"subject\": \"\", \"body\": \"\" }, { \"type\": \"reply5\", \"subject\": \"\", \"body\": \"\" }]}"
        ];

$p = '{response_format}


# 現在日時
{datetime}

# 完全厳守制約
 - 回答は過去のやりとりから会話の流れを重視すること - 回答は過去に送った会話を繰り返さないこと - 回答は存在する商品名・サービス名・作品名のみを使用すること - 回答は存在しない商品名・サービス名・作品名を作り出さないこと - 回答は「human」から来た最後のメッセージの内容に関連した返信を作成すること - 回答は「human」から来た最後のメッセージのと同程度の文字数に留めること - 質問されている場合、必ず質問に沿った回答をすること - 回答の語調（敬語・丁寧語・ため口）は「human」に合わせること - 回答は締めのあいさつや継続を促す文言は禁止する 出力仕様: - JSONスキーマを完全に厳守すること - 日本語以外の表現は禁止 - JSON形式にバックスラッシュや改行を含めないこと - JSON形式以外は一切出力しないこと

「キャラクター設定：ai」
{character_prompt}
「キャラクター設定：human」
{user_data}';
        $ChatAi = new ChatAi($this->openAIChat, ["model" => "huihui-ai/Qwen2.5-14B-Instruct-abliterated-v2", "temperature" => 0.3]);
        $response = $ChatAi->send($p,$in);
        $response_text = $response->getContents();
        echo $response_text;

        $this->assertIsString($response_text);
    }
}