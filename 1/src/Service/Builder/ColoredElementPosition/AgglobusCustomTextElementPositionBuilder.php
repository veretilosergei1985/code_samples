<?php

namespace CardPrinterService\Service\Builder\ColoredElementPosition;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Dto\TextDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Model\Customer;
use CardPrinterService\Service\Builder\CardImage\SimpleTextBuilder;
use CardPrinterService\Service\ImageHelper;

class AgglobusCustomTextElementPositionBuilder implements ColoredElementPositionBuilderInterface
{
    public function __construct(private ImageHelper $imageHelper)
    {
    }

    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === SimpleTextBuilder::TYPE
            && $element->getFieldType() === SimpleTextBuilder::FIELD_TYPE
            && $template->getCustomer() === Customer::AGGLOBUS;
    }

    /**
     * @param TextDto $element
     */
    public function build(ElementDto $element, PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, array &$points): void
    {
        $sizes = $this->imageHelper->getTextSize($element, $element->getTextContent());
        $points[] = [
            'x' => $element->getX(),
            'y' => $element->getY(),
            'w' => $sizes['width'],
            'h' => $sizes['height'],
        ];
    }
}
