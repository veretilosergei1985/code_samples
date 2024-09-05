<?php

namespace App\ZendeskBundle\Controller;

use App\Controller\AbstractController;
use App\Controller\Traits\DataTableTrait;
use App\ZendeskBundle\DataTable\PendingAuditDataTable;
use App\ZendeskBundle\DataTable\TransferObject\HistoryAuditFilterTransfer;
use App\ZendeskBundle\DataTable\TransferObject\PendingAuditFilterTransfer;
use App\ZendeskBundle\Service\DataTableRegistry;
use App\ZendeskBundle\Service\Report\AuditReportFiltersBuilder;
use Chernoff\Datatable\Manager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/quality', name: 'zendesk_quality_')]
class QualityController extends AbstractController
{
    use DataTableTrait;

    public function __construct(
        private Manager $dtManager,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(Request $request): Response
    {
        return $this->render('zendesk/quality/dashboard.html.twig', ['queryParams' => AuditReportFiltersBuilder::build($request)]);
    }

    #[Route('/report', name: 'report')]
    public function report(): Response
    {
        return $this->render('zendesk/quality/report.html.twig');
    }

    #[Route('/pending-audit/data', name: 'pending_audit_data')]
    public function pendingAuditData(Request $request, PendingAuditDataTable $dataTable): Response
    {
        return $this->getDataForTable($this->dtManager, $dataTable, [
            'filters' => (new PendingAuditFilterTransfer())->unpackFromRequest($request),
        ]);
    }

    #[Route('/history-audit/data/{type}', name: 'history_audit_data', requirements: [
        'type' => 'refund|aht|csat|callQuality|chatQuality',
    ])]
    public function historyAuditData(DataTableRegistry $dataTableRegistry, string $type, Request $request): Response
    {
        $dataTable = $dataTableRegistry->getDataTable(sprintf('history_%s_audit_table', $type));

        return $this->getDataForTable($this->dtManager, $dataTable, ['type' => $type, 'filters' => (new HistoryAuditFilterTransfer())->unpackFromRequest($request)]);
    }
}
