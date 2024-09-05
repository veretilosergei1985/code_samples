<?php

namespace CardPrinterService\Service\Builder\CardImage;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Dto\TextDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Service\FontPathProvider;
use Imagine\Gd\Font;
use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;

class PassengerFirstNameBuilder implements CardBuilderInterface
{
    public const TYPE = 'TEXT';
    public const FIELD_TYPE = 'FIRSTNAME';

    public function __construct(protected Imagine $imagine, private FontPathProvider $fontPathProvider)
    {
    }

    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === static::TYPE && $element->getFieldType() == static::FIELD_TYPE;
    }

    /**
     * @param TextDto $element
     */
    public function build(PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, ElementDto $element, ImageInterface $image): void
    {
        $font = new Font($this->fontPathProvider->getPath($element->getFont(), $element->getStyle()), $element->getSize(), (new RGB())->color($element->getColor()));
        $image->draw()->text($passengerDto->getFirstName(), $font, new Point($element->getX(), $element->getY()), 0);
    }
}
