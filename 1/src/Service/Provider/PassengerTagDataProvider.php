<?php

namespace CardPrinterService\Service\Provider;

use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Serializer\CardElementSerializer;
use CardPrinterService\Service\Http\MobilityAccountClient;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PassengerTagDataProvider
{
    public function __construct(
        protected MobilityAccountClient $mobilityAccountClient,
        protected CardElementSerializer $serializer
    ) {
    }

    public function getPassengerTagByFileNumber(string $fileNumber): object
    {
        /** @var array $passengerTagData */
        $passengerTagData = $this->mobilityAccountClient->get(sprintf('/api/passenger_tags?passenger.fileNumber.fileNumber[exact]=%s', $fileNumber));

        if (count($passengerTagData['hydra:member']) == 0) {
            throw new NotFoundHttpException('PassengerTag was not found.');
        }

        return $this->serializer->fromArray($passengerTagData['hydra:member'][0], PassengerTagDto::class);
    }
}
