<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Exception;
use PhpImap\Mailbox;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminMailController extends AbstractController
{
    #[Route('/admin/mailbox', name: 'app_admin_mailbox')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $admin_email = $user->getAdminEmail();
        $admin_email_password = $user->getAdminEmailPassword();

        $mails = [];

        if ($admin_email != null && $admin_email_password != null) {
            $hostname = '{angel-x-devil.fr:993/imap/ssl/novalidate-cert}INBOX'; // Todo : remove 'novalidate-cert'

            try {
                $mailbox = new Mailbox($hostname, $admin_email, $this->decryptPassword($admin_email_password));
                $mailsIds = $mailbox->searchMailbox();

                for ($i = 0; $i < count($mailsIds) and $i < 2; $i++) {
                    $mails[] = $mailbox->getMail($mailsIds[$i], false);
                }
            } catch (Exception $e) {

            }
        }

        dd($mails);

        return $this->render('admin/mail/mailbox.html.twig', [
            'mails' => $mails
        ]);
    }

    public function encryptPassword(string $password): string
    {
        $key = $this->getParameter('app.mail_encrypt_key');

        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted_password = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv.$encrypted_password);
    }

    function decryptPassword(string $encrypted_password): string
    {
        $key = $this->getParameter('app.mail_encrypt_key');

        $encrypted_password = base64_decode($encrypted_password);

        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($encrypted_password, 0, $iv_length);
        $encrypted_password = substr($encrypted_password, $iv_length);

        return openssl_decrypt($encrypted_password, 'AES-256-CBC', $key, 0, $iv);
    }
}
