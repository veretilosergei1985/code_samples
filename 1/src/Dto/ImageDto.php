<?php

namespace CardPrinterService\Dto;

class ImageDto extends ElementDto
{
    public string $color;
    public bool $isPrintable;
    public int $width;
    public int $height;
    public string $assetId;

    public function getColor(): string
    {
        return $this->color;
    }

    public function isPrintable(): bool
    {
        return $this->isPrintable;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getAssetId(): string
    {
        return $this->assetId;
    }
}
