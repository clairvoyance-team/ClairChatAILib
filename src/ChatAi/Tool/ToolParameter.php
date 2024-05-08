<?php

namespace Clair\Ai\ChatAi\Tool;

class ToolParameter
{

    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $required = true,
        public readonly JSONTypeEnum $type = JSONTypeEnum::String,
    ) {}

    static public function fromArray(array $parameter): self
    {
        return new self(
            $parameter['name'],
            $parameter['description'] ?? null,
            $parameter['required'] ?? true,
            $parameter['type'] ? JSONTypeEnum::from($parameter['type']) : JSONTypeEnum::String
        );
    }
}