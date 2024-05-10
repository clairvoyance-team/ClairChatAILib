<?php

namespace Clair\Ai\ChatAi\Tool;

class ToolFunctionParameter
{

    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $required = true,
        public readonly JSONTypeEnum $type = JSONTypeEnum::String,
    ) {}

    public static function fromArray(array $parameter): self
    {
        return new self(
            $parameter['name'],
            $parameter['description'] ?? null,
            $parameter['required'] ?? true,
            $parameter['type'] ? JSONTypeEnum::from($parameter['type']) : JSONTypeEnum::String
        );
    }

    public function convertToJsonArr(): array
    {
        $parameter_arr = [];
        $parameter_arr["type"] = $this->type->getJsonType();

        if (!is_null($this->description)) {
            $parameter_arr["description"] = $this->description;
        }

        $parameters_json_arr[$this->name] = $parameter_arr;

        return $parameters_json_arr;
    }
}