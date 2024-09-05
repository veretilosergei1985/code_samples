<?php

namespace CardPrinterService\Service\Builder\CardImage;

use CardPrinterService\Dto\ElementDto;
use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Dto\QrCodeDto;
use CardPrinterService\Entity\Template;
use CardPrinterService\Serializer\CardElementSerializer;
use CardPrinterService\Service\Http\MobilityAccountClient;
use CardPrinterService\Service\Provider\CustomerConfigurationProvider;
use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;

class PassengerTokenQrCodeBuilder extends QrCodeBuilder implements CardBuilderInterface
{
    public const TYPE = 'QRCODE';
    public const URL_TYPE = 'PASSENGER_TOKEN';

    public function __construct(
        protected Imagine $imagine,
        protected MobilityAccountClient $mobilityAccountClient,
        protected CardElementSerializer $serializer,
        private Security $security,
        private CustomerConfigurationProvider $customerConfigurationProvider
    ) {
        parent::__construct($imagine);
    }

    public function supports(Template $template, ElementDto $element): bool
    {
        return $element->getType() === static::TYPE && $element->getUrlType() == static::URL_TYPE;
    }

    /**
     * @param QrCodeDto $element
     */
    public function build(PassengerDto $passengerDto, PassengerTagDto $passengerTagDto, ElementDto $element, ImageInterface $image): void
    {
        $userDto = $this->security->getUser();
        if (!$userDto instanceof UserDto) {
            throw new \RuntimeException("Can't retrieve user from security storage");
        }

        /** @var array $shortenerUrlData */
        $shortenerUrlData = $this->customerConfigurationProvider->findByCustomerAndParameterName($userDto, 'Shortener.url');

        if (!isset($shortenerUrlData['value'][0])) {
            throw new NotFoundHttpException('Shortener.url parameter is not set for current customer.');
        }

        $element->content = sprintf('%s/%s/p/%s', $shortenerUrlData['value'][0]['Shortener.url'], $userDto->getCustomer(), $passengerTagDto->getToken());

        parent::build($passengerDto, $passengerTagDto, $element, $image);
    }
}
