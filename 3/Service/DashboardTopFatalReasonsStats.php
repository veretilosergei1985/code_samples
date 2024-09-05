<?php

namespace App\ZendeskBundle\Service;

use App\Entity\Agent;
use App\ZendeskBundle\Repository\ZendeskTicketAuditRepository;

class DashboardTopFatalReasonsStats
{
    public function __construct(
        private ZendeskTicketAuditRepository $ticketAuditRepository
    ) {
    }

    public function getTopFatalReasons(Agent $agent): array
    {
        $result = [];
        $callReasonsCnt = $this->ticketAuditRepository->getCallFatalReasonsCount($agent);
        $chatReasonsCnt = $this->ticketAuditRepository->getChatFatalReasonsCount($agent);
        $totalReasonsCount = array_merge($callReasonsCnt, $chatReasonsCnt);

        foreach ($totalReasonsCount as $item) {
            if (!isset($result[$item['reason']])) {
                $result[$item['reason']] = intval($item['cnt']);
            } else {
                $result[$item['reason']] += $item['cnt'];
            }
        }

        arsort($result);
        return array_slice($result, 0, 5);
    }
}
