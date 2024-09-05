<?php

namespace CardPrinterService\Controller;

use CardPrinterService\Dto\PassengerDto;
use CardPrinterService\Dto\PassengerTagDto;
use CardPrinterService\Repository\TemplateRepository;
use CardPrinterService\Service\Builder\CardBuilder;
use CardPrinterService\Service\Builder\CardImage\ElementsDtoCollectionBuilder;
use CardPrinterService\Service\Builder\ColoredElementPosition\ColoredElementPositionBuilder;
use CardPrinterService\Service\Provider\PassengerDataProvider;
use CardPrinterService\Service\Provider\PassengerTagDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Security;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;

#[AsController]
final class GetPrintableCardAction extends AbstractController
{
    public function __construct(
        private CardBuilder $cardBuilder,
        protected TemplateRepository $templateRepository,
        protected PassengerDataProvider $passengerDataProvider,
        protected PassengerTagDataProvider $passengerTagDataProvider,
        protected ElementsDtoCollectionBuilder $dtoCollectionBuilder,
        protected ColoredElementPositionBuilder $coloredElementPositionBuilder,
        private Security $security
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $userDto = $this->security->getUser();
        if (!$userDto instanceof UserDto) {
            throw new \RuntimeException("Can't retrieve user from security storage");
        }
        $template = $this->templateRepository->findOneBy(['id' => $request->get('templateId'), 'customer' => $userDto->getCustomer(), 'isEnabled' => true]);

        if (!$template) {
            throw new NotFoundHttpException('Template not found.');
        }

        /** @var string $passengerId */
        $passengerId = $request->get('passengerId');

        try {
            /** @var PassengerDto $passengerDto */
            $passengerDto = $this->passengerDataProvider->getPassengerByMicroserviceId($passengerId);
            /** @var PassengerTagDto $passengerTagDto */
            $passengerTagDto = $this->passengerTagDataProvider->getPassengerTagByFileNumber($passengerDto->getFileNumber()->getFileNumber());
            $elements = $this->dtoCollectionBuilder->build($template->getElements());

            $base64image = $this->cardBuilder->build($template, $elements, $passengerDto, $passengerTagDto);
            $coloredElements = $this->coloredElementPositionBuilder->build($template, $elements, $passengerDto, $passengerTagDto);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return new JsonResponse([
            'base64image' => $base64image,
            'coloredElements' => $coloredElements,
        ]);
    }
}
