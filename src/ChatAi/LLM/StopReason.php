<?php
namespace Clair\Ai\ChatAi\LLM;

enum StopReason
{
    // Common
    case End;

    case MaxTokens;

    case StopSequence;

    case Filter;

    case ToolCall;

    case Uncompleted;

    // Gemini
    case Safety;
    case ContentFilter;

}