<?php

namespace CardPrinterService\Serializer\ElementSerializer;

use CardPrinterService\Dto\QrCodeDto;
use CardPrinterService\Serializer\CardElementSerializer;

class QrCodeSerializer implements CardElementSerializerInterface
{
    public const TYPE = 'QRCODE';

    public function __construct(private CardElementSerializer $cardElementSerializer)
    {
    }

    public function supports(array $element): bool
    {
        return $element['type'] === static::TYPE;
    }

    public function fromArray(array $data): object
    {
        return $this->cardElementSerializer->fromArray($data, QrCodeDto::class);
    }
}
