<?php

namespace CardPrinterService\Service\Builder\CardImage;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\ImageDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Service\Http\MobilityAccountClient;
use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

class PassengerPhotoBuilder implements CardBuilderInterface
{
    public const TYPE = 'IMAGE';
    public const SOURCE_TYPE = 'PASSENGER_PICTURE';

    public function __construct(protected Imagine $imagine, protected MobilityAccountClient $mobilityAccountClient)
    {
    }

    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === static::TYPE && $element->getSourceType() == static::SOURCE_TYPE && $template->getType() !== 'ANONYMOUS';
    }

    /**
     * @param ImageDto $element
     */
    public function build(PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, ElementDto $element, ImageInterface $image): void
    {
        /** @var string $passengerPhoto */
        $passengerPhoto = $this->mobilityAccountClient->get(sprintf('/api/passengers/%s/picture', $passengerDto->getMicroserviceId()));
        $imageResource = $this->imagine->load($passengerPhoto);
        $image->paste($imageResource, new Point($element->getX(), $element->getY()));
    }
}
