<?php

namespace CardPrinterService\Service\Builder\CardImage;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Dto\QrCodeDto;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

class QrCodeBuilder
{
    public function __construct(protected Imagine $imagine)
    {
    }

    /**
     * @param QrCodeDto $element
     */
    public function build(PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, ElementDto $element, ImageInterface $image): void
    {
        $redundancyLevel = $this->getRedundancyLevel($element->getRedundancyLevel());
        list($backgroundRed, $backgroundGreen, $backgroundBlue) = sscanf($element->getBackgroundColor(), '#%02x%02x%02x'); /* @phpstan-ignore-line */
        list($foregroundRed, $foregroundGreen, $foregroundBlue) = sscanf($element->getForegroundColor(), '#%02x%02x%02x'); /* @phpstan-ignore-line */

        $writer = new PngWriter();
        $qrCode = QrCode::create($element->getContent())
            ->setEncoding(new Encoding('UTF-8'))
            ->setSize($element->getDimensions())
            ->setMargin($element->getMargin())
            ->setErrorCorrectionLevel($redundancyLevel)
            ->setForegroundColor(new Color((int) $foregroundRed, (int) $foregroundGreen, (int) $foregroundBlue))
            ->setBackgroundColor(new Color((int) $backgroundRed, (int) $backgroundGreen, (int) $backgroundBlue));

        $result = $writer->write($qrCode);

        /** @var resource $tmpFile */
        $tmpFile = fopen('php://temp', 'r+');
        fwrite($tmpFile, $result->getString());
        rewind($tmpFile);

        $imageResource = $this->imagine->read($tmpFile)->resize(new Box($element->getDimensions(), $element->getDimensions()));
        $image->paste($imageResource, new Point($element->getX(), $element->getY()));
        fclose($tmpFile);
    }

    protected function getRedundancyLevel(string $redundancyLevel): ErrorCorrectionLevelInterface
    {
        return match ($redundancyLevel) {  /* @phpstan-ignore-line */
            'L' => new ErrorCorrectionLevelLow(),
            'M' => new ErrorCorrectionLevelMedium(),
            'Q' => new ErrorCorrectionLevelQuartile(),
            'H' => new ErrorCorrectionLevelHigh(),
        };
    }
}
