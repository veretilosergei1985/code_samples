<?php

namespace CardPrinterService\Dto;

class QrCodeDto extends ElementDto
{
    public string $content;
    public string $backgroundColor;
    public string $foregroundColor;
    public string $redundancyLevel;
    public int $margin;
    public int $dimensions;

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    public function getForegroundColor(): string
    {
        return $this->foregroundColor;
    }

    public function getRedundancyLevel(): string
    {
        return $this->redundancyLevel;
    }

    public function getMargin(): int
    {
        return $this->margin;
    }
}
