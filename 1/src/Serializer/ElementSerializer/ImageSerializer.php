<?php

namespace CardPrinterService\Serializer\ElementSerializer;

use CardPrinterService\Dto\ImageDto;
use CardPrinterService\Serializer\CardElementSerializer;

class ImageSerializer implements CardElementSerializerInterface
{
    public const TYPE = 'IMAGE';

    public function __construct(private CardElementSerializer $cardElementSerializer)
    {
    }

    public function supports(array $element): bool
    {
        return $element['type'] === static::TYPE;
    }

    public function fromArray(array $data): object
    {
        return $this->cardElementSerializer->fromArray($data, ImageDto::class);
    }
}
