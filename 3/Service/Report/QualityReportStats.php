<?php

namespace App\ZendeskBundle\Service\Report;

use App\ZendeskBundle\Repository\ZendeskTicketRepository;

class QualityReportStats
{
    public function __construct(
        private ZendeskTicketRepository $ticketRepository,
    ) {
    }

    protected function getData(string $title, string $startDate, string $endDate): array
    {
        $result = [
            'title' => $title,
        ];

        $data = $this->ticketRepository->getQualityReportData($startDate, $endDate);
        $agentsData = [];
        $totalFatals = $totalAudits = 0;

        if (!$data) {
            return $result;
        }

        foreach($data as $item) {
            $quality = 0;

            if ($item['cntAudits'] && is_null($item['cntFatals'])) {
                $quality = 100;
            }
            if ($item['cntAudits'] && $item['cntFatals']) {
                $quality = ($item['cntAudits'] - $item['cntFatals']) / $item['cntAudits'] * 100;
            }

            $fatals = is_null($item['cntFatals']) ? 0 : $item['cntFatals'];
            $audits = is_null($item['cntAudits']) ? 0 : $item['cntAudits'];
            $totalFatals += $fatals;
            $totalAudits += $audits;

            $agentNameParts = explode('.', str_replace('_fridayplans', "", $item['username']));

            $agentsData[] = [
                'agentName' => implode(' ', array_map(fn ($agentName) => ucfirst($agentName), $agentNameParts)),
                'agentEmail' => $item['userEmail'],
                'audits' => $audits,
                'fatals' => $fatals,
                'quality' => number_format($quality, 2, '.', '')
            ];
        }

        $result['agentsData'] = $agentsData;
        $result['totalAudits'] = $totalAudits;
        $result['totalFatals'] = $totalFatals;
        $result['totalQuality'] = $totalAudits ? number_format(($totalAudits - $totalFatals) / $totalAudits * 100, 2, '.', '') : 0;

        return $result;
    }
}
