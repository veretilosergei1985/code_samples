<?php

namespace CardPrinterService\EventSubscriber;

use CardPrinterService\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Symfony\Component\Security\Core\Security;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Template::class)]
class TemplatePrePersistSubscriber implements EventSubscriber
{
    public function __construct(private readonly Security $security)
    {
    }

    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(Template $template): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserDto) {
            if ($_ENV['APP_ENV'] === 'test') {
                return;
            }

            throw new \Exception('User is not valid.');
        }

        $template->setCustomer($user->getCustomer());
    }
}
