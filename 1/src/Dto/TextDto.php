<?php

namespace CardPrinterService\Dto;

class TextDto extends ElementDto
{
    public string $color;
    public string $textContent;
    public string $font;
    public string $style;
    public int $size;

    public function getColor(): string
    {
        return $this->color;
    }

    public function getFont(): string
    {
        return $this->font;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getTextContent(): string
    {
        return $this->textContent;
    }
}
