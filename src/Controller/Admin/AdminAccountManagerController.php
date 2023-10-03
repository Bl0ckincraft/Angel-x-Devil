<?php

namespace App\Controller\Admin;

use App\Controller\Base\AbstractAppController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminAccountManagerController extends AbstractAppController
{
    #[Route('/admin/manager/account', name: 'app_admin_account_manager')]
    public function index(): Response
    {
        return $this->render('admin/manager/account.html.twig', [

        ]);
    }
}
