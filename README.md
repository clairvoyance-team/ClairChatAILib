# ClairChatAILib

Chatやツールの実行を簡単に行うためのライブラリです。

# Get Started

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
        echo $str;
        return $str;
    }
}
```

ツールをAIに渡して実行する
```php
$weather = new TestWeatherForecaster("晴子ちゃん");
$tool = [ToolFunction::readMethod($weather, "getCurrentWeather")];
$ChatAi = new ChatAi($this->open_ai_chat, ["model" => "gpt-4-turbo", "tool_choice" => "required"], $tool);
$response = $ChatAi->send("今日の東京の天気を教えて");
$result = $response->runTools(); //getCurrentWeatherの実行結果が入る
```
