<?php

namespace App\Controller;

use App\Controller\Base\AbstractAppController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AboutController extends AbstractAppController
{
    #[Route('/about', name: 'app_about')]
    public function index(): Response
    {
        return $this->render('about/about.html.twig');
    }
}
