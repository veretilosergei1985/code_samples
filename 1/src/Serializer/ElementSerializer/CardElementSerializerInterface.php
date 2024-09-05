<?php

namespace CardPrinterService\Serializer\ElementSerializer;

interface CardElementSerializerInterface
{
    public function supports(array $element): bool;

    public function fromArray(array $data): object;
}
