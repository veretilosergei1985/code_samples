<?php

namespace App\ZendeskBundle\Controller\Audit;

use App\Entity\Agent;
use App\Service\Survey\Action\Agent\GetAgent;
use App\ZendeskBundle\Entity\AuditInterface;
use App\ZendeskBundle\Entity\ZendeskAuditAht;
use App\ZendeskBundle\Entity\ZendeskAuditCsat;
use App\ZendeskBundle\Entity\ZendeskAuditRefund;
use App\ZendeskBundle\Entity\ZendeskCallAuditQuality;
use App\ZendeskBundle\Entity\ZendeskContact;
use App\ZendeskBundle\Entity\ZendeskTicketItem;
use App\ZendeskBundle\Enum\ZendeskAuditTypeEnum;
use App\ZendeskBundle\Form\Audit\AuditAhtType;
use App\ZendeskBundle\Form\Audit\AuditCsatType;
use App\ZendeskBundle\Form\Audit\AuditRefundType;
use App\ZendeskBundle\Form\Audit\CallAuditQualityType;
use App\ZendeskBundle\Form\Audit\ChatAuditQualityType;
use App\Controller\AbstractController;
use App\Form\FormHandler;
use App\Manager\BaseManager;
use App\Service\Survey\Action\Chat\GetChatInfo;
use App\ZendeskBundle\Entity\ZendeskChatAuditQuality;
use App\ZendeskBundle\Entity\ZendeskTicketAudit;
use App\ZendeskBundle\Form\Audit\CommentAuditType;
use App\ZendeskBundle\Repository\ZendeskTicketAuditRepository;
use App\ZendeskBundle\Service\NextAuditResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/review', name: 'audit_review_')]
class ReviewController extends AbstractController
{
    public function __construct(
        protected FormHandler $formHandler,
        protected GetChatInfo $getChatInfo,
        protected BaseManager $manager,
        protected EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/{type}/{id}/new', name: 'new', requirements: [
        'type' => 'refund|aht|csat|callQuality|chatQuality',
    ])]
    public function new(
        Request $request,
        ZendeskTicketAudit $ticketAudit,
        string $type,
        NextAuditResolver $auditResolver
    ): Response {
        $record = null;
        $url = $this->generateUrl('audit_review_new', ['id' => $ticketAudit->getId(), 'type' => $type]);

        $audit = $this->initAuditByType($type);
        $audit->setAuditor($this->entityManager->getRepository(Agent::class)->find($this->getUser()->getId()));
        if ($ticketAudit->getTicket()->getItems() && $ticketAudit->getTicket()->getChannel() == ZendeskContact::CONTACT_TYPE_VOICE) {
            $record = $ticketAudit->getTicket()->getItems()->filter(function (ZendeskTicketItem $ticketItem) {
                return $ticketItem->getType() === ZendeskContact::CONTACT_TYPE_VOICE;
            })->first();
            if ($record) {
                $record = $record->getMessage();
            }
        }

        $process = $this->formHandler
            ->buildWithAction($this->getAuditFormTypeByType($type), $url, $audit)
            ->process($request);

        if ($process) {
            $ticketAudit->{'set'.ucwords($type)}($audit);
            $ticketAudit->setCreatedAt(new \DateTime());
            $this->entityManager->persist($audit);
            $this->entityManager->flush();

            $audit = $auditResolver->resolve($ticketAudit);
            if ($audit->getTicket()->getChannel() == 'voice') {
                $type = 'callQuality';
            } elseif ($audit->getTicket()->getChannel() == 'chat' ||  $audit->getTicket()->getChannel() == 'email') {
                $type = 'chatQuality';
            }

            return $this->redirectToRoute('audit_review_new', [
                'id' => $audit->getId(),
                'type' => $type
            ]);
        }

        return $this->render('zendesk/audit/new/new.html.twig', [
            'type' => $type,
            'audit' => $audit,
            'ticketAudit' => $ticketAudit,
            'form' => $this->formHandler->createView(),
            'record' => $record,
        ]);
    }

    #[Route('/{type}/{id}/history', name: 'review', requirements: [
        'type' => 'refund|aht|csat|callQuality|chatQuality',
    ])]
    public function review(
        GetAgent $getAgent,
        int $id,
        string $type,
        Request $request
    ): Response {
        /** @var AuditInterface $audit */
        $audit = $this->getAuditByIdAndType($id, $type);

        if (!$audit) {
            throw new NotFoundHttpException('Audit not found');
        }
        $record = null;
        if ($audit->getAudit()->getTicket()->getItems() && $audit->getAudit()->getTicket()->getChannel() == ZendeskContact::CONTACT_TYPE_VOICE) {
            $record = $audit->getAudit()->getTicket()->getItems()->filter(function (ZendeskTicketItem $ticketItem) {
                return $ticketItem->getType() === ZendeskContact::CONTACT_TYPE_VOICE;
            })->first();
            if ($record) {
                $record = $record->getMessage();
            }
        }

        $form = $this->createForm(CommentAuditType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $audit->setStatus($data['status']);
            $audit->setComment($data['comment']);
            $this->entityManager->flush();
        }

        return $this->render('zendesk/audit/review/review.html.twig', [
            'type' => $type,
            'ticketAudit' => $audit->getAudit(),
            'audit' => $audit,
            'record' => $record,
            'agent' => $getAgent->execute($audit->getAuditor()->getApiAgentId()),
            'form' => $form->createView()
            //'chatInfo' => $this->getChatInfo->execute($analysis->getContactId()),
        ]);
    }

    #[Route('/{type}/{id}/edit', name: 'edit', requirements: [
        'type' => 'refund|aht|csat|callQuality|chatQuality',
    ])]
    public function edit(
        int $id,
        string $type,
        Request $request,
        ZendeskTicketAuditRepository $ticketAuditRepository
    ): Response {
        /** @var ZendeskTicketAudit $ticketAudit */
        $ticketAudit = $ticketAuditRepository->getAuditByIdAndType($id, $type);

        if (!$ticketAudit) {
            throw new NotFoundHttpException('Audit not found');
        }
        $record = null;
        $audit = $ticketAudit->{'get'.ucwords($type)}();
        if ($ticketAudit->getTicket()->getItems() && $ticketAudit->getTicket()->getChannel() == ZendeskContact::CONTACT_TYPE_VOICE) {
            $record = $ticketAudit->getTicket()->getItems()->filter(function (ZendeskTicketItem $ticketItem) {
                return $ticketItem->getType() === ZendeskContact::CONTACT_TYPE_VOICE;
            })->first();
            if ($record) {
                $record = $record->getMessage();
            }
        }

        $url = $this->generateUrl('audit_review_edit', ['id' => $ticketAudit->getId(), 'type' => $type]);
        $process = $this->formHandler
            ->buildWithAction($this->getAuditFormTypeByType($type), $url, $audit)
            ->process($request);

        if ($process) {
            if ($audit->getStatus()) {
                $audit->setViewed(true);
            }
            $this->entityManager->flush();
            return $this->redirectToRoute('zendesk_quality_dashboard');
        }

        return $this->render('zendesk/audit/edit/edit.html.twig', [
            'type' => $type,
            'audit' => $audit,
            'ticketAudit' => $ticketAudit,
            'form' => $this->formHandler->createView(),
            'record' => $record,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(
        ZendeskTicketAudit $audit,
        EntityManagerInterface $em,
        NextAuditResolver $auditResolver
    ): Response {
        $nextAudit = $auditResolver->resolve($audit);
        if ($nextAudit->getTicket()->getChannel() == 'voice') {
            $nextType = 'callQuality';
        } elseif ($nextAudit->getTicket()->getChannel() == 'chat' ||  $nextAudit->getTicket()->getChannel() == 'email') {
            $nextType = 'chatQuality';
        }

        $em->remove($audit->getTicket());
        $em->flush();

        return $this->redirectToRoute('audit_review_new', [
            'id' => $nextAudit->getId(),
            'type' => $nextType
        ]);
    }

    private function initAuditByType(string $type)
    {
        switch ($type) {
            case ZendeskAuditTypeEnum::CALL_QUALITY:
                return new ZendeskCallAuditQuality();
            case ZendeskAuditTypeEnum::CHAT_QUALITY:
                return new ZendeskChatAuditQuality();
            case ZendeskAuditTypeEnum::AHT:
                return new ZendeskAuditAht();
            case ZendeskAuditTypeEnum::CSAT:
                return new ZendeskAuditCsat();
            case ZendeskAuditTypeEnum::REFUND:
                return new ZendeskAuditRefund();
            default:
                throw new \Exception('Unknown type');
        }
    }

    private function getAuditFormTypeByType(string $type)
    {
        switch ($type) {
            case ZendeskAuditTypeEnum::CALL_QUALITY:
                return CallAuditQualityType::class;
            case ZendeskAuditTypeEnum::CHAT_QUALITY:
                return ChatAuditQualityType::class;
            case ZendeskAuditTypeEnum::AHT:
                return AuditAhtType::class;
            case ZendeskAuditTypeEnum::CSAT:
                return AuditCsatType::class;
            case ZendeskAuditTypeEnum::REFUND:
                return AuditRefundType::class;
            default:
                throw new \Exception('Unknown type');
        }
    }

    private function getAuditByIdAndType(int $id, string $type)
    {
        switch ($type) {
            case ZendeskAuditTypeEnum::CALL_QUALITY:
                return $this->entityManager->getRepository(ZendeskCallAuditQuality::class)->find($id);
            case ZendeskAuditTypeEnum::CHAT_QUALITY:
                return $this->entityManager->getRepository(ZendeskChatAuditQuality::class)->find($id);
            case ZendeskAuditTypeEnum::AHT:
                return $this->entityManager->getRepository(ZendeskAuditAht::class)->find($id);
            case ZendeskAuditTypeEnum::CSAT:
                return $this->entityManager->getRepository(ZendeskAuditCsat::class)->find($id);
            case ZendeskAuditTypeEnum::REFUND:
                return $this->entityManager->getRepository(ZendeskAuditRefund::class)->find($id);
            default:
                throw new \Exception('Unknown type');
        }
    }
}
