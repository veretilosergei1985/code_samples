<?php

namespace CardPrinterService\Service\Builder\ColoredElementPosition;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Entity\Template;
use Doctrine\Common\Collections\ArrayCollection;

class ColoredElementPositionBuilder
{
    /**
     * @param iterable|ColoredElementPositionBuilderInterface[] $builders
     */
    public function __construct(
        protected iterable $builders
    ) {
    }

    public function build(Template $template, ArrayCollection $elements, PassengerDto $passengerDto, PassengerTagDto $passengerTagDto): array
    {
        $points = [];

        try {
            /** @var ElementDto $element */
            foreach ($elements as $element) {
                /** @var ColoredElementPositionBuilderInterface $builder */
                foreach ($this->builders as $builder) {
                    if ($builder->supports($template, $element)) {
                        $builder->build($element, $passengerDto, $passengerTagDto, $points);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return array_values(array_unique($points, SORT_REGULAR));
    }
}
