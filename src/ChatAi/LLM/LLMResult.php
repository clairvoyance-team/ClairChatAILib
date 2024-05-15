<?php

namespace Clair\Ai\ChatAi\LLM;

use DateTime;

interface LLMResult
{

    public function getModelName(): string;

    /**
     * @return LLMResultChoice[]
     */
    public function getChoices() :array;


    /**
     * 生成した日時
     * @return DateTime
     */
    public function getCreatedAt() :DateTime;

    /**
     * トークン使用量
     * @return array{input_tokens: int, output_tokens: int}
     */
    public function getUsage() :array;
}