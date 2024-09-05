<?php

namespace CardPrinterService\Serializer\ElementSerializer;

use CardPrinterService\Dto\TextDto;
use CardPrinterService\Serializer\CardElementSerializer;

class TextSerializer implements CardElementSerializerInterface
{
    public const TYPE = 'TEXT';

    public function __construct(private CardElementSerializer $cardElementSerializer)
    {
    }

    public function supports(array $element): bool
    {
        return $element['type'] === static::TYPE;
    }

    public function fromArray(array $data): object
    {
        return $this->cardElementSerializer->fromArray($data, TextDto::class);
    }
}
