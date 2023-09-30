<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminSiteStatsController extends AbstractController
{
    #[Route('/admin/stats', name: 'app_admin_site_stats')]
    public function index(): Response
    {
        return $this->render('admin/stats.html.twig', [

        ]);
    }
}
