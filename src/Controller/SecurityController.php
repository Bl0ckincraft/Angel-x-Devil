<?php

namespace App\Controller;

use App\Controller\Base\AbstractAppController;
use App\Controller\Base\NotificationType;
use App\Entity\User;
use App\Form\RegisterFormType;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractAppController
{
    public function __construct(private EntityManagerInterface $entityManager, private MailerInterface $mailer)
    {

    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->getUser()) {
            if ($request->query->has('redirect_to')) {
                $redirection = $request->query->get('redirect_to');

                return $this->redirect($redirection);
            } else {
                return $this->redirectToRoute('app_account');
            }
        }

        $user = new User();
        $form = $this->createForm(RegisterFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($password);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $mail = (new Email())
                ->from(new Address('noreply@angel-x-devil.fr', 'Angel x Devil'))
                ->to($user->getEmail())
                ->subject('Création de votre compte')
                ->text('Votre compte a bien été créer !');

            $this->mailer->send($mail);

            return $this->redirectToRoute('app_login', $request->query->all());
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        if ($this->getUser()) {
            if ($request->query->has('redirect_to')) {
                $redirection = $request->query->get('redirect_to');

                return $this->redirect($redirection);
            } else {
                return $this->redirectToRoute('app_account');
            }
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            $this->addNotification(NotificationType::ERROR, $error->getMessage());
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'notifications' => $this->getNotifications()
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
