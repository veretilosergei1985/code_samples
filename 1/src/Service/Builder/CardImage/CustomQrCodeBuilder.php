<?php

namespace CardPrinterService\Service\Builder\CardImage;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Entity\Template;

class CustomQrCodeBuilder extends QrCodeBuilder implements CardBuilderInterface
{
    public const TYPE = 'QRCODE';
    public const URL_TYPE = 'CUSTOM';

    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === static::TYPE && $element->getUrlType() == static::URL_TYPE;
    }
}
