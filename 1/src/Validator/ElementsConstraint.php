<?php

namespace CardPrinterService\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ElementsConstraint extends Constraint
{
    public string $message = 'The elements property is not valid!';

    #[HasNamedArguments]
    public function __construct(string $message, array $groups = null, mixed $payload = null)
    {
        parent::__construct([], $groups, $payload);
        $this->message = $message;
    }

    public function validatedBy()
    {
        return static::class.'Validator';
    }
}
