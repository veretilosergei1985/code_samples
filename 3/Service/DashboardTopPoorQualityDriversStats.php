<?php

namespace App\ZendeskBundle\Service;

use App\Entity\Agent;
use App\ZendeskBundle\Enum\ZendeskAuditReasonEnum;
use App\ZendeskBundle\Repository\ZendeskTicketAuditRepository;

class DashboardTopPoorQualityDriversStats
{
    public function __construct(
        private ZendeskTicketAuditRepository $ticketAuditRepository
    ) {
    }

    public function getTopPoorQualityDrivers(Agent $agent): array
    {
        $result = $totalReasonsCount = [];
        foreach (ZendeskAuditReasonEnum::$reasonAuditTypeMap as $field => $auditType) {
            if ($auditType === 'both') {
                $callReasonsCnt = $this->ticketAuditRepository->getPoorQualityDriversCountByReason(self::camelCase2UnderScore($field), $agent, 'call');
                $chatReasonsCnt = $this->ticketAuditRepository->getPoorQualityDriversCountByReason(self::camelCase2UnderScore($field), $agent, 'chat');
                $totalReasonsCount = array_merge($callReasonsCnt, $chatReasonsCnt);
            }
            if ($auditType === 'call') {
                $totalReasonsCount = $this->ticketAuditRepository->getPoorQualityDriversCountByReason(self::camelCase2UnderScore($field), $agent, 'call');
            }
            if ($auditType === 'chat') {
                $totalReasonsCount = $this->ticketAuditRepository->getPoorQualityDriversCountByReason(self::camelCase2UnderScore($field), $agent, 'chat');
            }
            foreach ($totalReasonsCount as $item) {
                if (!isset($result[$item['reason']])) {
                    $result[$item['reason']] = intval($item['cnt']);
                } else {
                    $result[$item['reason']] += $item['cnt'];
                }
            }

        }
        arsort($result);
        return array_slice($result, 0, 5);
    }

    public static function camelCase2UnderScore($str, $separator = "_")
    {
        if (empty($str)) {
            return $str;
        }
        $str = lcfirst($str);
        $str = preg_replace("/[A-Z]/", $separator . "$0", $str);
        return strtolower($str);
    }
}
