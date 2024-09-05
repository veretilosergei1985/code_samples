<?php

namespace CardPrinterService\Service\Builder\CardImage;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\ImageDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Service\Provider\AssetMediaProvider;
use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

class AnonymousPassengerPhotoBuilder implements CardBuilderInterface
{
    public const TYPE = 'IMAGE';
    public const SOURCE_TYPE = 'CUSTOM';

    public function __construct(protected Imagine $imagine, protected AssetMediaProvider $assetMediaProvider)
    {
    }

    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === static::TYPE && $element->getSourceType() == static::SOURCE_TYPE && $template->getType() == 'ANONYMOUS';
    }

    /**
     * @param ImageDto $element
     */
    public function build(PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, ElementDto $element, ImageInterface $image): void
    {
        /** @var string $imageContent */
        $imageContent = file_get_contents($this->assetMediaProvider->getAssetMediaSignUrl($element->getAssetId()));
        /** @var string $base64imageContent */
        $base64imageContent = file_get_contents(sprintf('data:image/jpeg;base64,%s', base64_encode($imageContent)));
        $imageResource = $this->imagine->load($base64imageContent);
        $image->paste($imageResource, new Point($element->getX(), $element->getY()));
    }
}
