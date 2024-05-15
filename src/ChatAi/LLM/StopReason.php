<?php
namespace Clair\Ai\ChatAi\LLM;

enum StopReason
{
    case End;

    case MaxTokens;

    case StopSequence;

    case Filter;

    case ToolCall;

    case Uncompleted;
}