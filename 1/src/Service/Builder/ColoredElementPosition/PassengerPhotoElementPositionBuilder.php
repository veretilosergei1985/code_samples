<?php

namespace CardPrinterService\Service\Builder\ColoredElementPosition;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\ImageDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Model\Customer;
use CardPrinterService\Service\Builder\CardImage\PassengerPhotoBuilder;

class PassengerPhotoElementPositionBuilder implements ColoredElementPositionBuilderInterface
{
    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === PassengerPhotoBuilder::TYPE
            && $element->getSourceType() === PassengerPhotoBuilder::SOURCE_TYPE
            && in_array($template->getCustomer(), [Customer::AGGLOBUS, Customer::TULLAVAL, Customer::TULLAON, Customer::TREMA, Customer::HDF])
            && $template->getType() !== 'ANONYMOUS';
    }

    /**
     * @param ImageDto $element
     */
    public function build(ElementDto $element, PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, array &$points): void
    {
        $points[] = [
            'x' => $element->getX(),
            'y' => $element->getY(),
            'w' => $element->getWidth(),
            'h' => $element->getHeight(),
        ];
    }
}
