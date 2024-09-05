<?php

namespace CardPrinterService\Service\Provider;

use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Serializer\CardElementSerializer;
use CardPrinterService\Service\Http\MobilityAccountClient;

class PassengerDataProvider
{
    public function __construct(
        protected MobilityAccountClient $mobilityAccountClient,
        protected CardElementSerializer $serializer
    ) {
    }

    public function getPassengerByMicroserviceId(string $passengerId): object
    {
        /** @var array $passengerData */
        $passengerData = $this->mobilityAccountClient->get(sprintf('/api/passengers/%s', $passengerId));

        return $this->serializer->fromArray($passengerData, PassengerDto::class);
    }
}
