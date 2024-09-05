<?php

namespace CardPrinterService\Service\Builder;

use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Service\Builder\CardImage\CardBuilderRegistry;
use CardPrinterService\Service\Builder\CardImage\ElementsDtoCollectionBuilder;
use CardPrinterService\Service\GCSFileUploader;
use Doctrine\Common\Collections\ArrayCollection;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CardBuilder
{
    public function __construct(
        protected Imagine $imagine,
        protected CardBuilderRegistry $cardBuilderRegistry,
        protected ElementsDtoCollectionBuilder $dtoCollectionBuilder,
        protected GCSFileUploader $fileUploader
    ) {
    }

    public function build(Template $template, ArrayCollection $elements, PassengerDto $passengerDto, PassengerTagDto $passengerTagDto): string
    {
        try {
            $image = $this->imagine->create(new Box(1016, 648), (new RGB())->color('#FFF'));

            foreach ($elements as $element) {
                foreach ($this->cardBuilderRegistry->getBuilders() as $builder) {
                    if ($builder->supports($template, $element)) {
                        $builder->build($passengerDto, $passengerTagDto, $element, $image);
                    }
                }
            }

            /** @var resource $tmpFile */
            $tmpFile = tmpfile();
            $tmpPath = sprintf('%s.jpg', stream_get_meta_data($tmpFile)['uri']);
            $image->save($tmpPath);

            // Upload file to GCS
            $file = new UploadedFile($tmpPath, 'cards_'.$passengerDto->getMicroserviceId().'.jpg', 'image/jpeg', null, false);
            $this->fileUploader->upload($file);

            /** @var string $imageContent */
            $imageContent = file_get_contents($tmpPath);

            return base64_encode($imageContent);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }
}
