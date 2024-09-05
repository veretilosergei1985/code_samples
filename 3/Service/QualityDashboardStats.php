<?php

namespace App\ZendeskBundle\Service;

use App\Entity\Agent;
use App\ZendeskBundle\Repository\ZendeskCallAuditQualityRepository;
use App\ZendeskBundle\Repository\ZendeskChatAuditQualityRepository;
use App\ZendeskBundle\Repository\ZendeskTicketAuditRepository;

class QualityDashboardStats
{
    public function __construct(
        private ZendeskTicketAuditRepository $ticketAuditRepository,
        private ZendeskCallAuditQualityRepository $callAuditQualityRepository,
        private ZendeskChatAuditQualityRepository $chatAuditQualityRepository
    ) {
    }

    public function getTotalAuditsCount(Agent $agent): int
    {
        return intval($this->ticketAuditRepository->getTotalAuditsCount($agent));
    }

    public function getCurrentMonthTotalFatals(Agent $agent, string $startDate, string $endDate): int
    {
        return intval($this->ticketAuditRepository->getFatalAuditsCount($agent, $startDate, $endDate));
    }

    public function getTotalPendingAudits(Agent $agent): int
    {
        return intval($this->ticketAuditRepository->getTotalPendingAuditsCount($agent));
    }

    public function getCurrentMonthTotalScore(Agent $agent, string $startDate, string $endDate): float
    {
        return round(($this->chatAuditQualityRepository->getTotalScore($agent, $startDate, $endDate) +
            $this->callAuditQualityRepository->getTotalScore($agent, $startDate, $endDate)) / 2);
    }

    public function getAuditsCountsByDatePeriods(Agent $agent): array
    {
        $data = [];
        $startDate = new \DateTime('first day of this month');
        $startDate->setTime(0, 0, 0);
        $endDate = new \DateTime('last day of this month');
        $endDate->setTime(23, 59, 59);
        $interval = \DateInterval::createFromDateString('1 week');

        $datePeriod = new \DatePeriod($startDate, $interval, $endDate);
        foreach ($datePeriod as $firstIntervalDay) {
            $lastIntervalDay = clone $firstIntervalDay;
            $lastIntervalDay->add($interval);
            $data[] = [
                'startDate' => $firstIntervalDay->format('Y-m-d'),
                'endDate' => $lastIntervalDay->format('Y-m-d'),
                'pendingReviewAudits' => intval(
                    $this->ticketAuditRepository->getTotalPendingAuditsCount(
                        $agent,
                        $firstIntervalDay->format('Y-m-d 00:00:00'),
                        $lastIntervalDay->format('Y-m-d 23:59:59')
                    )
                ),
                'totalAudits' => intval(
                    $this->ticketAuditRepository->getTotalAuditsCount(
                        $agent,
                        $firstIntervalDay->format('Y-m-d 00:00:00'),
                        $lastIntervalDay->format('Y-m-d 23:59:59')
                    )
                ),
                'fatals' => intval(
                    $this->ticketAuditRepository->getFatalAuditsCount(
                        $agent,
                        $firstIntervalDay->format('Y-m-d 00:00:00'),
                        $lastIntervalDay->format('Y-m-d 23:59:59')
                    )
                ),
            ];
        }

        return $data;
    }

    public function getChatsAudited(Agent $agent): int
    {
        return (int) $this->chatAuditQualityRepository->countAllForAgent($agent);
    }

    public function getCallsAudited(Agent $agent): int
    {
        return (int) $this->callAuditQualityRepository->countAllForAgent($agent);
    }

    public function getTotalFatals(Agent $agent): int
    {
        return intval($this->ticketAuditRepository->getFatalAuditsCount($agent));
    }

    public function getTotalScore(Agent $agent): float
    {
        return round(($this->chatAuditQualityRepository->getTotalScore($agent) +
                $this->callAuditQualityRepository->getTotalScore($agent)) / 2);
    }
}
