<?php

namespace App\ZendeskBundle\Service;

use App\Entity\Agent;
use App\Repository\AgentRepository;
use App\Service\Guzzle\JsonRequest;
use App\Service\Survey\Action\Customer\SearchCustomer;
use App\ZendeskBundle\Entity\ZendeskContact;
use App\ZendeskBundle\Entity\ZendeskCustomer;
use App\ZendeskBundle\Entity\ZendeskTicket;
use App\ZendeskBundle\Entity\ZendeskTicketItem;
use App\ZendeskBundle\Http\ZendeskAuthenticatedClient;
use App\ZendeskBundle\Http\ZopimAuthenticatedClient;
use App\ZendeskBundle\Repository\ZendeskContactRepository;
use App\ZendeskBundle\Repository\ZendeskCustomerRepository;
use App\ZendeskBundle\Repository\ZendeskTicketItemRepository;
use App\ZendeskBundle\Repository\ZendeskTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ZendeskTicketManager
{
    private static $userRegistry = [];

    public function __construct(
        private EntityManagerInterface $em,
        private AgentRepository $agentRepository,
        private ZendeskTicketRepository $ticketRepository,
        private ZendeskCustomerRepository $zendeskCustomerRepository,
        private ZendeskContactRepository $contactRepository,
        private ZendeskTicketItemRepository $ticketItemRepository,
        private ZopimAuthenticatedClient $zopimAuthenticatedClient,
        private ZendeskAuthenticatedClient $zendeskAuthenticatedClient,
        private SearchCustomer $searchCustomer
    ) {
    }

    public function getAgent(int $agentId): ?Agent
    {
        $request = new JsonRequest(
            method: 'GET',
            uri: 'agents/'.$agentId
        );
        $response = $this->zopimAuthenticatedClient->send($request);
        if ($response->getStatusCode() !== 200) {
            return null;
        }
        $data = json_decode($response->getBody()->getContents(), true);

        return $this->agentRepository->findOneBy(['email' => $data['email']]);
    }

    public function createCustomer(int $customerId): ?ZendeskCustomer
    {
        $request = new JsonRequest(
            method: 'GET',
            uri: 'users/'.$customerId
        );
        $response = $this->zendeskAuthenticatedClient->send($request);
        if ($response->getStatusCode() !== 200) {
            return null;
        }
        $data = json_decode($response->getBody()->getContents(), true);
        $user = $data['user'];
        $customerEntity = $this->zendeskCustomerRepository->findOneBy(['externalId' => $customerId]);
        if (!$customerEntity && !in_array($user['id'], self::$userRegistry)) {
            $customerEntity = new ZendeskCustomer();
            $customerEntity->setExternalId($user['id']);
            $customerEntity->setEmail($user['email']);
            $customerEntity->setName($user['name']);
            $customerEntity->setPhone($user['phone']);
            if (isset($user['email'])) {
                $surveyCustomer = $this->searchCustomer->searchByParams(['email' => $user['email']]);
                if ($surveyCustomer) {
                    $customerEntity->setSurveyCustomerId($surveyCustomer->getId());
                    $customerEntity->setSurveyCustomerName(sprintf('%s %s', $surveyCustomer->getFirstName(), $surveyCustomer->getLastName()));
                }
            }
            $this->em->persist($customerEntity);
            self::$userRegistry[] = $user['id'];
        }

        return $customerEntity;
    }

    public function createComments(ZendeskTicket $ticket, OutputInterface $output)
    {
        $url = 'tickets/' . $ticket->getExternalId() . '/comments';
        do {
            $request = new JsonRequest(
                method: 'GET',
                uri: $url
            );
            $response = $this->zendeskAuthenticatedClient->send($request);
            if ($response->getStatusCode() !== 200) {
                continue;
            }
            $data = json_decode($response->getBody()->getContents(), true);
            foreach ($data['comments'] as $comment) {
                if ($comment['via']['channel'] === 'web' && $comment['public'] === true) {
                    $ticketItemEntity = $this->ticketItemRepository->findOneBy(['externalId' => $comment['id'], 'type' => ZendeskContact::CONTACT_TYPE_COMMENT]);
                    if (!$ticketItemEntity) {
                        $output->writeln(sprintf('New comment #%s', $comment['id']));
                        $ticketItemEntity = new ZendeskTicketItem();
                        $ticketItemEntity->setExternalId($comment['id']);
                        $ticketItemEntity->setContact(null);
                        $ticketItemEntity->setSenderType('Agent');
                        $ticketItemEntity->setType(ZendeskContact::CONTACT_TYPE_COMMENT);
                        $ticketItemEntity->setMessage($comment['body']);
                        $ticketItemEntity->setTicket($ticket);
                        $ticketItemEntity->setCreatedAt(new \DateTime($comment['created_at']));
                        $this->em->persist($ticketItemEntity);
                    }
                }

                if ($comment['via']['channel'] === 'web' && $comment['public'] === false && $comment['type'] === 'Comment') {
                    $ticketItemEntity = $this->ticketItemRepository->findOneBy(['externalId' => $comment['id'], 'type' => ZendeskContact::CONTACT_TYPE_PRIVATE_COMMENT]);
                    if (!$ticketItemEntity) {
                        $output->writeln(sprintf('New comment #%s', $comment['id']));
                        $ticketItemEntity = new ZendeskTicketItem();
                        $ticketItemEntity->setExternalId($comment['id']);
                        $ticketItemEntity->setContact(null);
                        $ticketItemEntity->setSenderType('Agent');
                        $ticketItemEntity->setType(ZendeskContact::CONTACT_TYPE_PRIVATE_COMMENT);
                        $ticketItemEntity->setMessage($comment['body']);
                        $ticketItemEntity->setTicket($ticket);
                        $ticketItemEntity->setCreatedAt(new \DateTime($comment['created_at']));
                        $this->em->persist($ticketItemEntity);
                    }
                }

                if ($comment['via']['channel'] === 'voice' && $comment['type'] === 'VoiceComment') {
                    $ticketItemEntity = $this->ticketItemRepository->findOneBy(['externalId' => $comment['id'], 'type' => ZendeskContact::CONTACT_TYPE_COMMENT]);
                    if (!$ticketItemEntity) {
                        $output->writeln(sprintf('New comment #%s', $comment['id']));
                        $ticketItemEntity = new ZendeskTicketItem();
                        $ticketItemEntity->setExternalId($comment['id']);
                        $ticketItemEntity->setContact(null);
                        $ticketItemEntity->setSenderType('Agent');
                        $ticketItemEntity->setMessage($comment['data']['recording_url']);
                        $ticketItemEntity->setType(ZendeskContact::CONTACT_TYPE_VOICE);
                        $ticketItemEntity->setTicket($ticket);
                        $ticketItemEntity->setCreatedAt(new \DateTime($comment['created_at']));
                        $this->em->persist($ticketItemEntity);
                    }
                }

                if ($comment['via']['channel'] === 'email' && (isset($comment['is_public']) && $comment['is_public'] === true || isset($comment['public']) && $comment['public'] === true)) {
                    $ticketItemEntity = $this->ticketItemRepository->findOneBy(['externalId' => $comment['id'], 'type' => ZendeskContact::CONTACT_TYPE_COMMENT]);
                    if (!$ticketItemEntity) {
                        $output->writeln(sprintf('New comment #%s', $comment['id']));
                        $ticketItemEntity = new ZendeskTicketItem();
                        $ticketItemEntity->setExternalId($comment['id']);
                        $ticketItemEntity->setContact(null);
                        $ticketItemEntity->setSenderType('Visitor');
                        $ticketItemEntity->setType(ZendeskContact::CONTACT_TYPE_COMMENT);
                        $ticketItemEntity->setMessage($comment['html_body']);
                        $ticketItemEntity->setTicket($ticket);
                        $ticketItemEntity->setCreatedAt(new \DateTime($comment['created_at']));
                        $this->em->persist($ticketItemEntity);
                    }
                }

                if ($comment['via']['channel'] === 'chat_transcript' && $comment['public'] === true && $comment['type'] === 'Comment') {
                    $ticketItemEntity = $this->ticketItemRepository->findOneBy(['externalId' => $comment['id'], 'type' => ZendeskContact::CONTACT_TYPE_COMMENT]);
                    if (!$ticketItemEntity) {
                        $output->writeln(sprintf('New comment #%s', $comment['id']));
                        $ticketItemEntity = new ZendeskTicketItem();
                        $ticketItemEntity->setExternalId($comment['id']);
                        $ticketItemEntity->setContact(null);
                        $ticketItemEntity->setSenderType('Agent');
                        $ticketItemEntity->setType(ZendeskContact::CONTACT_TYPE_COMMENT);
                        $ticketItemEntity->setMessage($comment['html_body']);
                        $ticketItemEntity->setTicket($ticket);
                        $ticketItemEntity->setCreatedAt(new \DateTime($comment['created_at']));
                        $this->em->persist($ticketItemEntity);
                    }
                }

                if (in_array($comment['via']['channel'], ['voice', 'system']) && $comment['type'] === 'Comment') {
                    $ticketItemEntity = $this->ticketItemRepository->findOneBy(['externalId' => $comment['id'], 'type' => ZendeskContact::CONTACT_TYPE_COMMENT]);
                    if (!$ticketItemEntity) {
                        $output->writeln(sprintf('New comment #%s', $comment['id']));
                        $ticketItemEntity = new ZendeskTicketItem();
                        $ticketItemEntity->setExternalId($comment['id']);
                        $ticketItemEntity->setContact(null);
                        $ticketItemEntity->setSenderType('Agent');
                        $ticketItemEntity->setType(ZendeskContact::CONTACT_TYPE_COMMENT);
                        $ticketItemEntity->setMessage($comment['body']);
                        $ticketItemEntity->setTicket($ticket);
                        $ticketItemEntity->setCreatedAt(new \DateTime($comment['created_at']));
                        $this->em->persist($ticketItemEntity);
                    }
                }
            }

            if (isset($data['next_page'])) {
                $url = $data['next_page'];
            }
        } while (isset($data['next_page']));
    }

    public function createContacts()
    {
        $url = 'chats';
        do {
            $request = new JsonRequest(
                method: 'GET',
                uri: $url
            );
            $response = $this->zopimAuthenticatedClient->send($request);
            if ($response->getStatusCode() !== 200) {
                continue;
            }
            $data = json_decode($response->getBody()->getContents(), true);

            foreach ($data['chats'] as $chat) {
                $chatEntity = $this->contactRepository->findOneBy(['externalId' => $chat['id'], 'type' => ZendeskContact::CONTACT_TYPE_CHAT]);
                if ($chatEntity) {
                    continue;
                }
                $chatEntity = new ZendeskContact();
                $chatEntity->setExternalId($chat['id']);
                $chatEntity->setStartDate(new \DateTime($chat['session']['start_date']));
                $chatEntity->setEndDate(new \DateTime($chat['session']['end_date']));
                $chatEntity->setDuration($chat['duration']);
                $chatEntity->setType(ZendeskContact::CONTACT_TYPE_CHAT);
                $chatEntity->setTicket($this->ticketRepository->findOneBy(['externalId' => $chat['zendesk_ticket_id']]));

                if (isset($chat['history'])) {
                    $this->createTicketItems($chat['history'], $chatEntity);
                }

                $this->em->persist($chatEntity);
            }

            if (isset($data['next_url'])) {
                $url = $data['next_url'];
            }
        } while (isset($data['next_url']));
        $this->em->flush();
    }

    public function createTicketItems(array $data, ZendeskContact $chatEntity)
    {
        foreach ($data as $historyItem) {
            if (isset($historyItem['sender_type']) &&
                in_array($historyItem['sender_type'], ['Trigger', 'Visitor', 'Agent']) &&
                (isset($historyItem['trigger_id']) || isset($historyItem['msg_id']))
            ) {
                $params = [];
                switch ($historyItem['sender_type']) {
                    case 'Trigger':
                        $params = ['senderType' => 'Trigger', 'externalId' => $historyItem['trigger_id'], 'type' => 'chat'];
                        break;
                    case 'Visitor':
                        $params = ['senderType' => 'Visitor', 'externalId' => $historyItem['msg_id'], 'type' => 'chat'];
                        break;
                    case 'Agent':
                        $params = ['senderType' => 'Agent', 'externalId' => $historyItem['msg_id'], 'type' => 'chat'];
                        break;
                }
                $ticketItemEntity = $this->ticketItemRepository->findOneBy($params);
                if (!$ticketItemEntity) {
                    $ticketItemEntity = new ZendeskTicketItem();
                    $ticketItemEntity->setExternalId($params['externalId']);
                    $ticketItemEntity->setContact($chatEntity);
                    $ticketItemEntity->setSenderType($params['senderType']);
                    $ticketItemEntity->setMessage($historyItem['msg']);
                    $ticketItemEntity->setType('chat');
                    $ticketItemEntity->setTicket($chatEntity->getTicket());
                    $ticketItemEntity->setCreatedAt(new \DateTime($historyItem['timestamp']));
                    $this->em->persist($ticketItemEntity);
                }
            }
        }
    }
}
