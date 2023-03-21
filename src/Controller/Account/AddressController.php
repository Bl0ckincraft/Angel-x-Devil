<?php

namespace App\Controller\Account;

use App\Controller\Base\AbstractAppController;
use App\Controller\Base\NotificationType;
use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AddressController extends AbstractAppController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    #[Route('/account/address/add', name: 'app_add_address')]
    public function addAddress(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $address = new Address();
        $address->setUser($user);
        $address->setFirstname($user->getFirstname());
        $address->setLastname($user->getLastname());
        $address->setPhone($user->getPhone());
        $address->setCountry('FR');

        $form = $this->createForm(AddressFormType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($address);
            $this->entityManager->flush();

            $this->addNotification(NotificationType::SUCCESS, 'Votre adresse a bien été ajoutée.');

            if ($request->query->has('redirect_to')) {
                $redirection = $request->query->get('redirect_to');

                return new RedirectResponse($redirection.'?notifications='.$this->encodeNotifications());
            } else {
                return $this->redirectToRoute('app_account', [
                    'notifications' => $this->encodeNotifications()
                ]);
            }
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form,
            'type' => 'add'
        ]);
    }

    #[Route('/account/address/edit/{id}', name: 'app_edit_address')]
    public function editAddress(Request $request, $id): Response
    {
        /** @var Address $address */
        $address = $this->entityManager->getRepository(Address::class)->find($id);
        /** @var User $user */
        $user = $this->getUser();

        if (!$address || $address->getUser()->getId() != $user->getId()) {
            $this->addNotification(NotificationType::ERROR, 'Cette adresse n\'a pas été trouvée.');

            return $this->redirectToRoute('app_account', [
                'notifications' => $this->encodeNotifications()
            ]);
        }

        $form = $this->createForm(AddressFormType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addNotification(NotificationType::SUCCESS, 'Votre adresse a bien été modifiée.');
            return $this->redirectToRoute('app_account', [
                'notifications' => $this->encodeNotifications()
            ]);
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form,
            'type' => 'edit'
        ]);
    }

    #[Route('/account/address/delete/{id}', name: 'app_delete_address')]
    public function deleteAddress($id): Response
    {
        /** @var Address $address */
        $address = $this->entityManager->getRepository(Address::class)->find($id);

        if (!$address) {
            $this->addNotification(NotificationType::ERROR, 'Cette adresse n\'a pas été trouvée.');

            return $this->redirectToRoute('app_account', [
                'notifications' => $this->encodeNotifications()
            ]);
        }

        $this->entityManager->remove($address);
        $this->entityManager->flush();

        $this->addNotification(NotificationType::SUCCESS, 'Votre adresse a bien été supprimée.');

        return $this->redirectToRoute('app_account', [
            'notifications' => $this->encodeNotifications()
        ]);
    }
}
