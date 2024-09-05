<?php

namespace CardPrinterService\Service\Builder\CardImage;

use CardPrinterService\Serializer\ElementSerializer\CardElementSerializerInterface;
use Doctrine\Common\Collections\ArrayCollection;

class ElementsDtoCollectionBuilder
{
    /**
     * @param iterable|CardElementSerializerInterface[] $serializers
     */
    public function __construct(protected iterable $serializers)
    {
    }

    public function build(array $elements): ArrayCollection
    {
        $elementsCollection = new ArrayCollection();

        try {
            foreach ($elements as $element) {
                foreach ($this->serializers as $serializer) {
                    if ($serializer->supports($element)) {
                        $elementsCollection->add($serializer->fromArray($element));
                    }
                }
            }
        } catch (\Exception $e) {
            dd($e);
        }

        return $elementsCollection;
    }
}
