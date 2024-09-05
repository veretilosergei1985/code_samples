<?php

namespace CardPrinterService\Service\Builder\CardImage;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Entity\Template;
use Imagine\Image\ImageInterface;

interface CardBuilderInterface
{
    public function supports(Template $template, ElementDto $element): bool;

    public function build(PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, ElementDto $element, ImageInterface $image): void;
}
