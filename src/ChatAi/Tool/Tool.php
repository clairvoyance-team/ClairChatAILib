<?php

namespace Clair\Ai\ChatAi\Tool;

interface Tool
{
    /**
     * Request用のデータ形式に変換する
     * @return array
     */
    public function toRequestArr() :array;

}