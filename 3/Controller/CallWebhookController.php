<?php

namespace App\ZendeskBundle\Controller;

use App\ZendeskBundle\Message\CallTicketMessage;
use App\ZendeskBundle\Repository\ZendeskCustomerSatisfactionSmsLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/call', name: 'call_', methods: ['POST'])]
class CallWebhookController extends AbstractController
{
    public function __construct(private MessageBusInterface $bus, private ZendeskCustomerSatisfactionSmsLogRepository $customerSatisfactionSmsLogRepository)
    {
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $data = $request->toArray();

        if (!isset($data['id'])) {
            throw new BadRequestException();
        }

        $this->bus->dispatch(new CallTicketMessage($data['id']));

        return new Response();
    }

    #[Route('/customer-feedback', name: 'customer_feedback')]
    public function customerSmsFeedback(Request $request): Response
    {
        $response = json_decode($request->getContent());

        if ($response->data->event_type == "message.received") {
            $customerSatisfactionSmsLog = $this->customerSatisfactionSmsLogRepository->getCustomerLatestLogByPhone($response->data->payload->from->phone_number);
            if ($customerSatisfactionSmsLog) {
                $customerSatisfactionSmsLog->setText($response->data->payload->text);
                $this->customerSatisfactionSmsLogRepository->save($customerSatisfactionSmsLog);
            }
        }

        return new Response();
    }
}