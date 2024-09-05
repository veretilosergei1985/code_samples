<?php

namespace App\ZendeskBundle\Service;

use App\Entity\Agent;
use App\Repository\AgentRepository;
use App\ZendeskBundle\Entity\ZendeskTicketAudit;
use App\ZendeskBundle\Repository\ZendeskTicketAuditRepository;

class NextAuditResolver
{
    public function __construct(private AgentRepository $agentRepository, private ZendeskTicketAuditRepository $ticketAuditRepository)
    {
    }

    public function resolve(ZendeskTicketAudit $ticketAudit): ?ZendeskTicketAudit
    {
        $agent = $this->getNextAgent($ticketAudit->getTicket()->getAgent());
        do {
            $audit = $this->ticketAuditRepository->getNextCancelRefundAudit($agent);
            if (!$audit) {
                $audit = $this->ticketAuditRepository->getNextAudit($agent);
            }
            if (!$audit) {
                $agent = $this->getNextAgent($agent);
            }
        } while ($audit == null);

        return $audit;
    }

    private function getNextAgent(?Agent $agent): Agent
    {
        if (!$agent) {
            return $this->agentRepository->getFirstAgent();
        }
        $agent = $this->agentRepository->getNextAgent($agent);
        if (!$agent) {
            $agent = $this->agentRepository->getFirstAgent();
        }

        return $agent;
    }
}
