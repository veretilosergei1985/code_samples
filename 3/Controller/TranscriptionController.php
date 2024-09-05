<?php

namespace App\ZendeskBundle\Controller;

use App\Controller\AbstractController;
use App\ZendeskBundle\Entity\ZendeskContact;
use App\ZendeskBundle\Entity\ZendeskTicketAudit;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/transcription', name: 'zendesk_transcription_')]
class TranscriptionController extends AbstractController
{
    #[Route('/{id}/list', name: 'list', options: ['expose' => true])]
    public function list(ZendeskTicketAudit $ticketAudit): JsonResponse
    {
        $data = [];
        foreach ($ticketAudit->getTicket()->getItems() as $ticketItem) {
            if (in_array($ticketItem->getType(), [ZendeskContact::CONTACT_TYPE_COMMENT, ZendeskContact::CONTACT_TYPE_CHAT, ZendeskContact::CONTACT_TYPE_PRIVATE_COMMENT])) {
                $data[] = [
                    'role' => $ticketItem->getSenderType(),
                    'sentiment' => 'NEUTRAL',
                    'type' => $ticketItem->getType(),
                    'content' => $ticketItem->getMessage(),
                ];
            }
        }

        return new JsonResponse($data);
    }
}
