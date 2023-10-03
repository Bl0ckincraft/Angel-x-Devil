<?php

namespace App\Controller\Admin;

use App\Controller\Base\AbstractAppController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminOrderManagerController extends AbstractAppController
{
    #[Route('/admin/manager/order', name: 'app_admin_order_manager')]
    public function index(): Response
    {
        return $this->render('admin/manager/order.html.twig', [

        ]);
    }
}
