<?php
namespace Clair\Ai\ChatAi\LLM\LocalLLM;

use Clair\Ai\ChatAi\LLM\OpenAi\OpenAIResponseChoice;
use Clair\Ai\ChatAi\Message\AIMessage;
use Clair\Ai\ChatAi\Message\Content\ToolCallingContent;
use Clair\Ai\ChatAi\Message\Message;
use Clair\Ai\ChatAi\Tool\Tool;
use Clair\Ai\ChatAi\Tool\ToolType;
use OpenAI\Responses\Chat\CreateResponseChoice;

class LocalLLMResponseChoice
{

    public function __construct(
        public readonly int $index,
        public readonly Message $message,
        public readonly ?string $finish_reason
    ) {
    }

    /**
     * @param CreateResponseChoice $choice
     * @param Tool[] $tools
     * @return OpenAIResponseChoice
     */
    public static function fromCreateResponseChoice(CreateResponseChoice $choice, array|null $tools): LocalLLMResponseChoice
    {
        if ($choice->message->toolCalls && !is_null($tools)) {
            //ツール呼び出しの場合
            $tool_calling_contents= [];
            foreach ($choice->message->toolCalls as $tool_call) {
                $tool_call_obj = Tool::getMatchingToolCallInstance(
                    $tools,
                    ToolType::from($tool_call->type),
                    $tool_call->function->name,
                    json_decode($tool_call->function->arguments, true)
                );
                $tool_calling_contents[] = new ToolCallingContent($tool_call->id, ToolType::from($tool_call->type), $tool_call_obj);
            }

            $message = new AIMessage($tool_calling_contents);

        } else {

            //普通のテキスト
            $message = new AIMessage($choice->message->content);
        }

        return new self($choice->index, $message, $choice->finishReason);
    }
}