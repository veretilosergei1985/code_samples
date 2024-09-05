<?php

namespace App\ZendeskBundle\Service\Report;

class FtdStats extends QualityReportStats
{
    public function getFtdData(): array
    {
        return $this->getData(
            sprintf('FTD as of %s', (new \DateTime())->format('dS F Y')),
            (new \DateTime())->format('Y-m-d 00:00:00'),
            (new \DateTime())->format('Y-m-d 23:59:59')
        );
    }
}
