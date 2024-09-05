<?php

namespace CardPrinterService\Service\Builder\ColoredElementPosition;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Dto\TextDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Model\Customer;
use CardPrinterService\Service\Builder\CardImage\PassengerCardNumberBuilder;
use CardPrinterService\Service\ImageHelper;

class CardNumberElementPositionBuilder implements ColoredElementPositionBuilderInterface
{
    public function __construct(private ImageHelper $imageHelper)
    {
    }

    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === PassengerCardNumberBuilder::TYPE
        && $element->getFieldType() === PassengerCardNumberBuilder::FIELD_TYPE
        && in_array($template->getCustomer(), [Customer::EVALYS, Customer::AGGLOBUS]);
    }

    /**
     * @param TextDto $element
     */
    public function build(ElementDto $element, PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, array &$points): void
    {
        $sizes = $this->imageHelper->getTextSize($element, $passengerTagDto->getId());
        $points[] = [
            'x' => $element->getX(),
            'y' => $element->getY(),
            'w' => $sizes['width'],
            'h' => $sizes['height'],
        ];
    }
}
