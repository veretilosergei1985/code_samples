<?php

namespace App\ZendeskBundle\Controller\Report;

use App\Controller\AbstractController;
use App\Form\FormHandler;
use App\Manager\BaseManager;
use App\Service\Survey\Action\Chat\GetChatInfo;
use App\ZendeskBundle\Service\Report\FtdStats;
use App\ZendeskBundle\Service\Report\MtdStats;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/report', name: 'zendesk_quality_report_')]
class ReportController extends AbstractController
{
    public function __construct(
        protected FormHandler $formHandler,
        protected GetChatInfo $getChatInfo,
        protected BaseManager $manager,
        protected EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/ftd', name: 'ftd', options: ['expose' => true])]
    public function getFtdStats(FtdStats $ftdStats)
    {
        return $this->json($ftdStats->getFtdData());
    }

    #[Route('/mtd', name: 'mtd', options: ['expose' => true])]
    public function getMtdStats(MtdStats $mtdStats)
    {
        return $this->json($mtdStats->getMtdData());
    }
}
