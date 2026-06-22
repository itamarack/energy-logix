<?php

namespace App\DTOs;

readonly class FormulaVersionData
{
    public function __construct(
        public string $name,
        public string $expression,
        public ?string $description = null,
        public array $variables = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            expression: $data['expression'],
            description: $data['description'] ?? null,
            variables: $data['variables'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'expression' => $this->expression,
            'description' => $this->description,
            'variables' => $this->variables,
        ];
    }
}
