<?php

namespace App\Controller\Account;

use App\Controller\Base\AbstractAppController;
use App\Controller\Base\NotificationType;
use App\Entity\Address;
use App\Entity\Order;
use App\Entity\User;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractAppController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    #[Route('/account', name: 'app_account')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->decodeNotifications($request);

        /** @var User $user */
        $user = $this->getUser();

        $changePasswordForm = $this->createForm(ChangePasswordFormType::class, $user);
        $changePasswordForm->handleRequest($request);

        if ($changePasswordForm->isSubmitted() && $changePasswordForm->isValid()) {
            $oldPassword = $changePasswordForm->get('old_password')->getData();

            if ($passwordHasher->isPasswordValid($user, $oldPassword)) {
                $newPassword = $changePasswordForm->get('new_password')->getData();
                $newHashedPassword = $passwordHasher->hashPassword($user, $newPassword);

                $user->setPassword($newHashedPassword);
                $this->entityManager->flush();

                $this->addNotification(NotificationType::SUCCESS, 'Votre mot de passe a bien été mis à jour.');
            } else {
                $this->addNotification(NotificationType::ERROR, 'Votre mot de passe actuel est incorrect.');
            }
        }

        $paidOrders = $this->entityManager->getRepository(Order::class)->findPaidOrders($user);

        return $this->render('account/account.html.twig', [
            'change_password_form' => $changePasswordForm->createView(),
            'notifications' => $this->getNotifications(),
            'paid_orders' => $paidOrders
        ]);
    }
}
