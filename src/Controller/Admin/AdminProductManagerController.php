<?php

namespace App\Controller\Admin;

use App\Controller\Base\AbstractAppController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminProductManagerController extends AbstractAppController
{
    #[Route('/admin/manager/product', name: 'app_admin_product_manager')]
    public function index(): Response
    {
        return $this->render('admin/manager/product.html.twig', [

        ]);
    }
}
