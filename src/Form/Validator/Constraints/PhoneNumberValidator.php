<?php

namespace App\Form\Validator\Constraints;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PhoneNumberValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        $phoneUtils = PhoneNumberUtil::getInstance();

        try {
            if (!$phoneUtils->isValidNumber($phoneUtils->parse($value))) {
                $this->context->buildViolation('Numéro de téléphone invalide.')
                    ->addViolation();
            }
        } catch (NumberParseException $ignored) {
            $this->context->buildViolation('Numéro de téléphone invalide.')
                ->addViolation();
        }
    }
}