<?php

namespace Clair\Ai\ChatAi\LLM;

interface Parameters
{
    public function toRequestArr() :array;
}