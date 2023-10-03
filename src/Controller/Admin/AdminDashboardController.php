<?php

namespace App\Controller\Admin;

use App\Controller\Base\AbstractAppController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractAppController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }
}
