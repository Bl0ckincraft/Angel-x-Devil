<?php

namespace App\Form\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class PhoneNumber extends Constraint
{
    public function __construct(array $groups = null, mixed $payload = null)
    {
        parent::__construct([], $groups, $payload);
    }
}
