<?php

namespace Clair\Ai\ChatAi\Tool;

use Clair\Ai\ChatAi\Tool\Exception\NotFoundMatchingToolException;

abstract class Tool
{
    /**
     * Request用のデータ形式に変換する
     * @return array
     */
    abstract public function toRequestArr() :array;

    abstract public function getType() :ToolType;

    abstract public function getToolCallInstance(array|null $input_arguments) :ToolCall;

    /**
     * 第一引数の$tools(Tool[]) の中で$typeと$nameが一致するToolが含まれたToolCallインスタンスを返す
     * @param Tool[] $tools 検索するToolリスト
     * @param ToolType $type
     * @param string $name
     * @param array<string, mixed> $input_arguments AIが生成した引数
     * @return ToolCall
     * @throws NotFoundMatchingToolException
     */
    public static function getMatchingToolCallInstance(array $tools, ToolType $type, string $name, array|null $input_arguments) :ToolCall
    {
        foreach ($tools as $tool) {
            if ($tool->getType() === $type && $name === $tool->name) {
                return $tool->getToolCallInstance($input_arguments);
            }
        }

        throw new NotFoundMatchingToolException("ツールが見つからないエラーが発生しました。");
    }
}