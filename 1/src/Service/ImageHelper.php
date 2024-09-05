<?php

namespace CardPrinterService\Service;

use CardPrinterService\Dto\TextDto;
use Imagine\Gd\Font;
use Imagine\Image\Palette\RGB;

class ImageHelper
{
    public function __construct(private FontPathProvider $fontPathProvider)
    {
    }

    public function getTextSize(TextDto $elementDto, string $string): array
    {
        $font = new Font($this->fontPathProvider->getPath($elementDto->getFont(), $elementDto->getStyle()), $elementDto->getSize(), (new RGB())->color($elementDto->getColor()));
        /** @var array $box */
        $box = imageftbbox($font->getSize(), 0, $font->getFile(), $string);
        $increaseBy = ceil(abs(($box[5] - $box[1]) / 5)) > 5 ? ceil(abs(($box[5] - $box[1]) / 5)) : 5;

        $width = abs($box[4] - $box[0]);
        $height = abs($box[5] - $box[1]) + $increaseBy;

        return [
            'width' => $width,
            'height' => $height,
        ];
    }
}
