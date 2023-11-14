<?php

namespace App\Controller\Admin;

use App\Controller\Base\AbstractAppController;
use App\Entity\MailDraft;
use App\Entity\User;
use App\Form\MailFormType;
use App\Utils\MailFormData;
use App\Utils\MailUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminMailController extends AbstractAppController
{
    public function __construct(private EntityManagerInterface $entityManager, private KernelInterface $kernel, private SluggerInterface $slugger) {

    }

    #[Route('/admin/mailbox/{box}', name: 'app_admin_mailbox')]
    public function readBox(string $box): Response
    {
        if (!array_key_exists($box, MailUtils::$boxData)) {
            throw $this->createNotFoundException('Mailbox not found.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $key = $this->getParameter('app.mail_encrypt_key');

        $admin_email = $user->getAdminEmail();
        $admin_email_password = $user->getAdminEmailPassword();
        $decrypted_admin_email_password = $admin_email_password ? MailUtils::decryptPasswordWithKey($admin_email_password, $key) : null;

        return $this->render('admin/mail/mailbox.html.twig', [
            'mails' => MailUtils::readBox($admin_email, $decrypted_admin_email_password, MailUtils::$boxData[$box]['imapName'], $this->entityManager),
            'box' => $box,
            'boxName' => MailUtils::$boxData[$box]['displayName']
        ]);
    }

    #[Route('/admin/mailbox/attachment/{msId}/{fileName}', name: 'app_admin_mailbox_attachment')]
    public function getAttachment(int $msId, string $fileName, KernelInterface $kernel): BinaryFileResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $admin_email = $user->getAdminEmail();

        $folder = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . "attachment" . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . MailUtils::getMailName($admin_email);
        $finalPath = $folder . DIRECTORY_SEPARATOR . $msId . "-" . $fileName;
        $realPath = realpath($finalPath);

        if (false === $realPath || !is_file($realPath)) {
            throw $this->createNotFoundException('Attachment not found.');
        }

        $response = new BinaryFileResponse($realPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        return $response;
    }

    #[Route('/admin/mailbox/{box}/read/{id}', name: 'app_admin_mailbox_read')]
    public function read(string $box, int $id, KernelInterface $kernel): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $key = $this->getParameter('app.mail_encrypt_key');

        $admin_email = $user->getAdminEmail();
        $admin_email_password = $user->getAdminEmailPassword();
        $decrypted_admin_email_password = $admin_email_password ? MailUtils::decryptPasswordWithKey($admin_email_password, $key) : null;

        return $this->render('admin/mail/mail.html.twig', [
            'mail' => MailUtils::readMail($admin_email, $decrypted_admin_email_password, MailUtils::$boxData[$box]['imapName'], $id, $this->entityManager, $kernel),
            'box' => $box,
            'boxName' => MailUtils::$boxData[$box]['displayName']
        ]);
    }


    #[Route('/admin/mailbox/write/new', name: 'app_admin_mailbox_write')]
    public function write(Request $request) : Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $key = $this->getParameter('app.mail_encrypt_key');

        $admin_email = $user->getAdminEmail();
        $admin_email_password = $user->getAdminEmailPassword();
        $decrypted_admin_email_password = $admin_email_password ? MailUtils::decryptPasswordWithKey($admin_email_password, $key) : null;

        if (!$admin_email || !$decrypted_admin_email_password) return new Response(status: 400);

        $mail = new MailFormData();

        if ($request->query->has("answer")) {
            // TODO
        } else if ($request->query->has("transfer")) {
            // TODO
        }

        $form = $this->createForm(MailFormType::class, $mail);
        $form->handleRequest($request);

        /** @var MailFormData $mail */
        $mail = $form->getData();
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $mail->send($admin_email, $decrypted_admin_email_password, $admin_email, $user->getFullName(), $this->entityManager, $this->kernel, $this->slugger);
                return $this->redirectToRoute('app_admin');
            } catch (\Exception $e) {
                dd($e);
            }
        }

        $mail->clearAttachments();
        return $this->render('admin/mail/write.html.twig', [
            'form' => $form
        ]);
    }
}
