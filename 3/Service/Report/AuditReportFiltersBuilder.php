<?php

namespace App\ZendeskBundle\Service\Report;

use Symfony\Component\HttpFoundation\Request;

class AuditReportFiltersBuilder
{
    public static function build(Request $request): array
    {
        $queryParams = [];
        $agentEmail = $request->get('agentEmail');
        $type = $request->get('type');
        $fatal = $request->get('fatal');

        if (!$agentEmail || !$type) {
            return $queryParams;
        }

        if ($type === 'ftd') {
            $queryParams['startDate'] = (new \DateTime())->format('Y-m-d');
        }

        if ($type === 'mtd') {
            $queryParams['startDate'] = (new \DateTime('first day of this month'))->format('Y-m-d');
        }

        if ($fatal) {
            $queryParams['fatal'] = 1;
        }

        $queryParams['endDate'] = (new \DateTime())->format('Y-m-d');
        $queryParams['agentEmail'] = $agentEmail;

        return $queryParams;
    }
}