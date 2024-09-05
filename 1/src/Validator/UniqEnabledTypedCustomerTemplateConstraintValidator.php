<?php

namespace CardPrinterService\Validator;

use CardPrinterService\Entity\Template;
use CardPrinterService\Repository\TemplateRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;

class UniqEnabledTypedCustomerTemplateConstraintValidator extends ConstraintValidator
{
    public function __construct(private TemplateRepository $templateRepository, private readonly Security $security)
    {
    }

    /**
     * @param Template $template
     */
    public function validate($template, Constraint $constraint): void
    {
        if (!$template instanceof Template) {
            throw new UnexpectedValueException($template, Template::class);
        }

        if (!$constraint instanceof UniqEnabledTypedCustomerTemplateConstraint) {
            throw new UnexpectedValueException($constraint, UniqEnabledTypedCustomerTemplateConstraint::class);
        }

        if ($template->getIsEnabled()) {
            /** @var UserDto $user */
            $user = $this->security->getUser();
            $existingTemplate = $this->templateRepository->findActiveTemplateByParams(
                $user->getCustomer(),
                $template->getType(),
                $template->getProduct(),
                $template->getId()
            );
            if ($existingTemplate) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->atPath('type')
                    ->addViolation();
            }
        }
    }
}
