<?php

namespace CardPrinterService\Service\Builder\ColoredElementPosition;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Dto\TextDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Model\Customer;
use CardPrinterService\Service\Builder\CardImage\PassengerFileNumberBuilder;
use CardPrinterService\Service\ImageHelper;

class FileNumberNotAnonymousElementPositionBuilder implements ColoredElementPositionBuilderInterface
{
    public function __construct(private ImageHelper $imageHelper)
    {
    }

    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === PassengerFileNumberBuilder::TYPE
            && $element->getFieldType() === PassengerFileNumberBuilder::FIELD_TYPE
            && in_array($template->getCustomer(), [
                Customer::SAINTLOAGGLO,
                Customer::EVALYS,
                Customer::CAGP,
                Customer::CASO,
                Customer::AGGLOBUS,
            ])
            && $template->getType() !== 'ANONYMOUS';
    }

    /**
     * @param TextDto $element
     */
    public function build(ElementDto $element, PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, array &$points): void
    {
        $sizes = $this->imageHelper->getTextSize($element, $passengerDto->fileNumber->fileNumber);
        $points[] = [
            'x' => $element->getX(),
            'y' => $element->getY(),
            'w' => $sizes['width'],
            'h' => $sizes['height'],
        ];
    }
}