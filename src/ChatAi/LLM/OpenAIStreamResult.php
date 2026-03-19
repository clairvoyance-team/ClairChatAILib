<?php

namespace Clair\Ai\ChatAi\LLM;

use DateTime;
use IteratorAggregate;
use OpenAI\Responses\StreamResponse;


class OpenAIStreamResult implements LLMResult, IteratorAggregate
{
    /**
     * @param StreamResponse $stream OpenAI SDKから返ってくるストリームオブジェクト
     * @param array|null $tools
     */
    public function __construct(
        private readonly StreamResponse $stream,
        private readonly ?array $tools = null
    ) {
    }

    /**
     * foreach で回した時に 1文字（1トークン）ずつ返却する
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->stream as $response) {
            // ストリーミングの場合、message ではなく delta というキーに内容が入ります
            $text = $response->choices[0]->delta->content ?? null;
            if ($text !== null) {
                yield $text;
            }
        }
    }

    // --- LLMResult インターフェースで強制されているメソッドの仮実装 ---
    // ストリーミング中は確定しない情報が多いため、必要に応じて実装を調整してください。

    public function getChoices(): array { return []; }
    public function getModelName(): string { return ""; }
    public function getCreatedAt(): \DateTime { return new DateTime(); }
    public function getUsage(): array { return []; }
}