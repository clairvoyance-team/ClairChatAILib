# ClairChatAILib

Chatやツールの実行を簡単に行うためのライブラリです。

# Composerでの導入方法
PHPのあるコンテナorサーバ でcomposer.jsonのあるディレクトリに移動して下さい<br>
```shell
composer config repositories.clairvoyance/chat-ai-lib vcs https://github.com/clairvoyance-team/ClairChatAILib
```
```shell
composer require clairvoyance/chat-ai-lib:{バージョン}
```

ここでGithubのトークンを生成しろと言われます。親切にURLを出してくれてると思うので、ブラウザで開いてトークンを生成してください。その後、そのトークンをコマンドに打ちます。<br>
`Token (hidden):`

成功すると
`Token stored successfully.` でインストールが始まります

# Get Started

### 単一テキストを送る

```php
$llm = OpenAIChatCompletion::from("OpenAI:APIKey");
$ChatAi = new ChatAi($llm, ["model" => "gpt-3.5-turbo"]);
$response = $ChatAi->send("沖縄のおすすめの料理を教えて");
$response_text = $response->getContents();
```

### ツールの実行

ツールのクラス
```php
class TestWeatherForecaster {

    public function __construct(
        public readonly string $name
    ) {
    }

    /**
     * 現在の天気を取得する
     * @param string $location 日本語の文字列で場所を示す
     * @return string
     */
    public function getCurrentWeather(string $location) :string
    {
        $str = $this->name . ":" . $location . "は晴れです";
        return $str;
    }
}
```

ツールをAIに渡して実行する
```php
$weather = new TestWeatherForecaster("晴子ちゃん");
$tools = [ToolFunction::readMethod($weather, "getCurrentWeather")];
$ChatAi = new ChatAi($this->open_ai_chat, ["model" => "gpt-4-turbo", "tool_choice" => "required"], $tools);
$response = $ChatAi->send("今日の東京の天気を教えて");
$result = $response->runTools(); //getCurrentWeatherの実行結果が入る
```

## Table of Contents
- [Get Started](#get-started)
- [Usage](#usage)
  - [Parameter](#parameter)
  - [get Response](#get-response)
  - [Prompt&History](#prompttemplate-and-history)
- [主な概念](#model)
  - [Message](#message)
  - [Content](#content)
  - [Prompt](#prompt)
  - [ChatHistory](#chathistory)
  - [Tool](#tool)
  - [LLM](#llm)

# Usage
使い方
基本的にはtestsディレクトリの中を見ればわかるかも。
<br>
<br>
# Parameter
第二引数でパラメータを指定できます。\[パラメータ名 => 値, ...\] <br>
パラメータの種類については各LLMParameterクラスのプロパティもしくはAPIを参照してください。
```php
$llm = OpenAIChatCompletion::from("API-key");
$ChatAi = new ChatAi($llm, ["model" => "gpt-3.5-turbo", "max_tokens" => 500, "n" => 2]);
$response = $ChatAi->send("沖縄のおすすめの料理を教えて");

//レスポンスがテキストのみの場合、getContents()で文字列を取得できます なお選択肢の1個目のテキストを取得します。
$response_text = $response->getContents();
```

# get Response
```php
$response = $ChatAi->send(...);

//2番目の選択肢のAIMessageを取得します ない場合はnull
$response_text = $response->getChoiceMessage(1);

//選択肢すべてのAIMessageを取得したい
$all_choice_messages = $response->getAllChoiceMessages();

//選択肢1個目で呼び出されたツールをすべて取得したい
$tools = $response->getTools();

//選択肢2個目で呼び出されたツールをすべて取得したい
$tools = $response->choices[1]->getTools();

//今回AIに送ったメッセージ群を取得したい
$sent_messages = $response->getSentMessages();
```

# PromptTemplate And History
メッセージの中に動的に変化する値を入れたい場合は、プロンプトテンプレートを使ってください。<br>
またチャット履歴を挿入する方法も紹介します。

```php
$llm = OpenAIChatCompletion::from("API-key");
$ChatAi = new ChatAi($llm, ["model" => "gpt-3.5-turbo"]);

//テンプレート変数は「input_language」「output_language」になる
//チャットプロンプトテンプレートは、メッセージテンプレートもしくはMessageの配列を入れてください。
$prompt = new ChatPromptTemplate([
    new SystemMessagePromptTemplate("あなたは{input_language}を{output_language}に翻訳するアシスタントです"),
    new HumanTextMessagePromptTemplate("次の文章を{output_language}に翻訳して「おMessageすすめの料理を教えて」")
]);

$chat_history = new ChatMessageHistory();
$chat_history->addAIMessage("AIです");
$chat_history->addUserMessage("Userです");

//上記のHistoryは以下と同じ意味になります
//$chat_history = new ChatMessageHistory([new AIMessage("AIです"), new HumanMessage("Userです")]);

//ChatPromptTemplateの最後にChatHistoryのMessageを追加します
$prompt->appendChatHistory($chat_history);

//プロンプトとテンプレート変数を代入する　この時テンプレート変数は全て代入しなくてはいけない！
$response = $ChatAi->send($prompt, ["input_language" => "日本語", "output_language" => "英語"]);

//レスポンスがテキストのみの場合、getContents()で文字列を取得できます
$response_text = $response->getContents();
```


# Model
主な概念は以下です。

# Message
役割ごとにMessageクラスが分かれています。Messageは複数のContentを持つ場合があります。

- `SystemMessage`<br>
AIに前提条件や役割の説明・指示を行うメッセージ。Messageリストの最初に入れること<br>
↳ 1つのTextContentを持つ
    
- `HumanMessage`
 ユーザのメッセージ<br>
 ↳ 複数のTextContent/複数のImageContentを持てる
 
- `AIMessage`
AIのメッセージ<br>
 ↳ 複数のTextContent/複数のToolCallingContent(ツール呼び出し)を持てる

- `ToolMessage`
ツールの実行結果メッセージ<br>
↳ 1つのTextContentとtool_call_id(ツール呼び出しのid)を持つ。ツール呼び出しに対してツールの結果を送るメッセージ


# Content
Messageの内容を表す

- `TextContent`
普通のテキスト

- `ImageContent`
画像　urlかbase64データで表す

- `ToolCallingContent`
ツールの呼び出しを表す。ツール呼び出し時のIDと1つのToolCallオブジェクトを持つ
  -  `ToolCall` 呼び出されたツール自体・ツールの名前・AIが考えたツールの引数を持つ


# Prompt
プロンプトテンプレート
メッセージの内容に事前に変数を埋め込むことができます。変数に代入したい値を後から指定でき、Messageに変換できます。

```php
$template = "あなたは {input_language} を {output_language} に翻訳するアシスタントです。";
$message_template = new SystemMessagePromptTemplate($template);

$input_variables = ["input_language" => "英語", "output" => "フランス語"];
$result = $message_template->formatMessages($input_variables);

//$result
//SystemMessage("あなたは 英語 を フランス語 に翻訳するアシスタントです。");
```


プロンプトにはメッセージプロンプトとチャットプロンプトがあります

### MessagePromptTemplate
1メッセージ単位のプロンプトテンプレートになります。BaseMessagePromptを継承する必要があります。

```php
$template = "あなたは {input_language} を {output_language} に翻訳するアシスタントです。";
$message_template = new SystemMessagePromptTemplate($template);

$input_variables = ["input_language" => "英語", "output" => "フランス語"];
$result = $message_template->formatMessages($input_variables);
```

- `HumanTextMessagePromptTemplate`
- `SystemMessagePromptTemplate`

### ChatPromptTemplate
複数のメッセージプロンプトとMessageから構成されます。
複数のメッセージプロンプトのテンプレート変数に対して、値を一度に代入できます。代入後は、複数のMessageから構成されるChatPromptValueになります。

```php
$template_system = "あなたは {input_language} を {output} に翻訳するアシスタントです。";
$system_message_template = new SystemMessagePromptTemplate($template_system);

$template_human = "以下の {input_language} を翻訳してください。また{num}文字以内に収めてください。";
$human_message_template = new HumanTextMessagePromptTemplate($template_human);

$human_message = new HumanMessage("I love cats.", "alice");

$this->chatPrompt = new ChatPromptTemplate([$system_message_template, $human_message_template, $human_message]);

$input = ["input_language" => "英語", "output" => "フランス語", "num" => 200];
$result = $this->chatPrompt->formatPrompt($input);

//$result
//ChatPromptValue([
//     SystemMessage("あなたは 英語 を フランス語 に翻訳するアシスタントです。"),
//     HumanMessage("以下の 英語 を翻訳してください。また200文字以内に収めてください。"),
//     HumanMessage("I love cats.", "alice")
//]);
```

# ChatHistory
メッセージ履歴を格納するオブジェクトです。基本的にシステムメッセージはチャット履歴に含まないと考えています。
ChatHistoryはChatPromptTemplateに追加することができます。

```php
$chat_history = new ChatMessageHistory([new AIMessage("AIです")]);
$chat_history->addUserMessage("Userです");

//ChatPromptTemplate $chatPrompt
$chatPrompt->appendChatHistory($chat_history);

$prompt_value = $chatPrompt->formatPrompt($input);
//$prompt_value
//ChatPromptValue([
//     SystemMessage("ChatPromptTemplateに含まれてたもの"),
//     AIMessage("AIです"),
//     HumanMessage("Userです")
//]);
```

# Tool
AIにツールの定義を渡すことができます。
AIは関数をいつ呼び出す必要があるかをメッセージに応じて検出し、入力するべき適切な引数を返してくれます。

## ToolFunction
2024/05/15現在、Toolの種類はToolFunctionのみとなります。

ツールの定義には、PHPスクリプト等から読み込む方法と、配列で定義する方法があります。

## 配列で定義
```php
$expected = new ToolFunction(
    "getCurrentWeather", //メソッド名
    "Get the current weather in a given location", //説明
    [
          //引数名, 説明, 必須か, 型
          new ToolFunctionParameter("location", "The city and state, e.g. San Francisco, CA", true, JSONTypeEnum::Integer),
          new ToolFunctionParameter("unit", "文字列", false, JSONTypeEnum::String),
          new ToolFunctionParameter("str2", null, false, JSONTypeEnum::String),
     ],
     null //インスタンスor名前空間付きクラス名
);
```

## インスタンスor staticメソッドで定義
この場合、定義されたメソッド名・引数名がそのままToolFunctionになります。PHPDocでメソッド・引数の説明をAIに渡すことができます。またデフォルト値の有無で引数が必須かどうか決まります。
引数の型は int|string|array|float|bool のみが対応可です。

該当クラス
```php
class WeatherForecaster {

    public function __construct(
        public readonly string $name
    ) {
    }

    /**
     * 現在の天気を取得する
     * @param string $location 日本語の文字列で場所を示す
     * @param string $unit
     * @return string
     */
    public function getCurrentWeather(string $location, string $unit) :string
    {
        $str = $this->name . ":" . $location . "は晴れです";
        return $str;
    }

     /**
     * 現在の気温を取得する
     * @param string $location
     * @param string $format
     * @return string
     */
    public static function getCurrentTemperature(string $location, string $format="celsius"): string
    {
        $str = $location . "24" . $format;
        return $str;
    }
}
```

### readMethod
**第1引数**
- クラスの通常メソッドの場合はインスタンスを渡してください。その場合インスタンスのプロパティなどがそのまま使えます。

- staticメソッドの場合は、名前空間を含めクラス名を渡してください。

**第2引数**
メソッド名を渡してください

```php
$weather = new WeatherForecaster("晴子ちゃん");
$tool = ToolFunction::readMethod($weather, "getCurrentWeather");
$tool2 = ToolFunction::readMethod("tests\WeatherForecaster", "getCurrentTemperature")
```

## ToolFunctionのメソッドを実行
ToolFunctionの第4引数で該当のクラスのインスタンスorクラス名を渡すことで、ツールの自動実行が可能となります。

```php
$result = $tool->run(["location" => "Tokyo", "unit" => "celsius"]);
//$result
//晴子ちゃん:Tokyoは晴れです。
```

# LLM
ChatPromptValueやTools等を渡すことで、生成リクエストが可能となります。

## OpenAIChatCompletion

```php
$llm = OpenAIChatCompletion::from("API-key");
prompt_value = new ChatPromptValue([
      new SystemMessage("あなたは日本語を話すアドバイザーです。"),
      new HumanMessage("こんにちは！")
]);
$result = $this->openAIChat->convertChatPromptToArr($prompt_value, $tools);
```
## LocalLLMCompletion

```php
$llm = LocalLLMCompletion::from("http://118.238.8.76:8080/v1","blackwell");
$response = $llm->send("沖縄のおすすめの料理を教えて");
```

# 開発tips

## APIキーの管理
APIキーはnv.testingファイルに書いて、`getenv()`で呼び出す

```.env.testing
OPEN_AI_API_KEY=xxxxx
GEMINI_API_KEY=xxxxxx
LOCAL_LLM_API_KEY=xxxxxx
```

## テストの書き方
APIを叩く系のテストはローカルでのみ実行するよう下の記述を付けてください。
```aiignore
#[Group('local-only')]
class ChatAIOpenAITest extends TestCase
```


以下でテストができる
```shell
# 全部
./vendor/bin/phpunit --testdox tests
# local-onlyのみ
./vendor/bin/phpunit --group local-only
# local-onlyを除外
./vendor/bin/phpunit --testdox tests --exclude-group local-only
```

ブラウザ等はないので、基本的にテストコードで開発していく
