<?php

namespace CardPrinterService\Service\Builder\CardImage;

class CardBuilderRegistry
{
    /**
     * @var array
     */
    private $builders = [];

    public function add(CardBuilderInterface $builder): void
    {
        $this->builders[] = $builder;
    }

    public function getBuilders(): array
    {
        return $this->builders;
    }
}
