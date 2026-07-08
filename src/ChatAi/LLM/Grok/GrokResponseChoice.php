<?php

namespace Clair\Ai\ChatAi\LLM\Grok;

use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\Message;
use Clair\Ai\ChatAi\Tool\Tool;
use Clair\Ai\ChatAi\Tool\ToolType;

class GrokResponseChoice
{
    public function __construct(
        public readonly int $index,
        public readonly Message $message,
        public readonly ?string $finish_reason
    ) {
    }

    /**
     * @param Tool[]|null $tools
     */
    public static function fromStdClassChoice(\stdClass $choice, ?array $tools): self
    {
        $message = $choice->message ?? new \stdClass();
        $tool_calls = $message->tool_calls ?? null;

        if (is_array($tool_calls) && $tool_calls !== [] && !is_null($tools)) {
            $tool_calling_contents = [];
            foreach ($tool_calls as $tool_call) {
                $tool_type = ToolType::from($tool_call->type ?? ToolType::Function->value);
                $tool_name = $tool_call->function->name ?? '';
                $arguments = $tool_call->function->arguments ?? '{}';
                $decoded_arguments = is_string($arguments)
                    ? json_decode($arguments, true) ?? []
                    : (array) $arguments;

                $tool_call_obj = Tool::getMatchingToolCallInstance(
                    $tools,
                    $tool_type,
                    $tool_name,
                    $decoded_arguments
                );

                $tool_calling_contents[] = new ToolCallingContent(
                    $tool_call->id ?? '',
                    $tool_type,
                    $tool_call_obj
                );
            }

            $ai_message = new AIMessage($tool_calling_contents);
        } else {
            $content = $message->content ?? '';
            if (!is_string($content)) {
                $content = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            }
            $ai_message = new AIMessage($content);
        }

        return new self(
            (int) ($choice->index ?? 0),
            $ai_message,
            $choice->finish_reason ?? null
        );
    }
}

