<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GeneralConditionsController extends AbstractController
{
    #[Route('/cgu_cgv', name: 'app_general_conditions')]
    public function index(): Response
    {
        return $this->render('general_conditions/conditions.html.twig');
    }
}
