<?php

namespace App\Form\Input;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class ShowHidePasswordType extends AbstractType
{
    public function getParent(): string
    {
        return PasswordType::class;
    }
}