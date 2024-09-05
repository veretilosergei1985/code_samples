<?php

namespace CardPrinterService\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqEnabledTypedCustomerTemplateConstraint extends Constraint
{
    public string $message = 'Only one template can be enabled for one type';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
