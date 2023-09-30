<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminOrderManagerController extends AbstractController
{
    #[Route('/admin/manager/order', name: 'app_admin_order_manager')]
    public function index(): Response
    {
        return $this->render('admin/manager/order.html.twig', [

        ]);
    }
}
