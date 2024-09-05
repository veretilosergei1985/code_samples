<?php

namespace CardPrinterService\Dto;

class ElementDto
{
    public int $x;
    public int $y;
    public string $type;
    public ?string $urlType;
    public ?string $fieldType;
    public ?string $sourceType;

    public function getType(): string
    {
        return $this->type;
    }

    public function getFieldType(): ?string
    {
        return $this->fieldType;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }

    public function getUrlType(): ?string
    {
        return $this->urlType;
    }
}
