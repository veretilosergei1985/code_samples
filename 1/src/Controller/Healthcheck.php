<?php

namespace CardPrinterService\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class Healthcheck extends AbstractController
{
    #[Route('/healthcheck', name: 'healthcheck')]
    public function index(): JsonResponse
    {
        return $this->json('OK');
    }
}
