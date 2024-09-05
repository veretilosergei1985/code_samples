<?php

namespace App\ZendeskBundle\Service\Report;

class MtdStats extends QualityReportStats
{
    public function getMtdData(): array
    {
        return $this->getData(
            sprintf('MTD From 1st to %s', (new \DateTime())->format('dS F Y')),
            (new \DateTime('first day of this month'))->format('Y-m-d 00:00:00'),
            (new \DateTime())->format('Y-m-d 23:59:59')
        );
    }
}
