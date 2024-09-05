<?php

namespace CardPrinterService\Service\Factory;

use Imagine\Gd\Imagine;

class ImagineFactory
{
    public static function create(): Imagine
    {
        return new Imagine();
    }
}
