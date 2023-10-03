<?php

namespace App\Controller\Admin;

use App\Controller\Base\AbstractAppController;
use App\Entity\User;
use Exception;
use PhpImap\Mailbox;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminMailController extends AbstractAppController
{
    #[Route('/admin/mailbox', name: 'app_admin_mailbox')]
    public function mailbox(): Response
    {
        return $this->readBox('INBOX');
    }

    #[Route('/admin/mailbox/sent', name: 'app_admin_mailbox_sent')]
    public function sent(): Response
    {
        return $this->readBox('Sent');
    }

    #[Route('/admin/mailbox/trash', name: 'app_admin_mailbox_trash')]
    public function trash(): Response
    {
        return $this->readBox('Trash');
    }

    #[Route('/admin/mailbox/spam', name: 'app_admin_mailbox_spam')]
    public function spam(): Response
    {
        return $this->readBox('Junk');
    }

    #[Route('/admin/mailbox/drafts', name: 'app_admin_mailbox_drafts')]
    public function archive(): Response
    {
        return $this->readBox('Drafts');
    }

    #[Route('/admin/test', name: 'app_admin_test')]
    public function test(): Response
    {
        dd($this->getParameter('app.mail_encrypt_key'), $this->encryptPassword("wdIAt4OoM5WulsurG2vwk2hVEd6atjHD"));
    }

    public function readBox(string $box): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $admin_email = $user->getAdminEmail();
        $admin_email_password = $user->getAdminEmailPassword();

        $mails = [];

        if ($admin_email != null && $admin_email_password != null) {
            $hostname = '{angel-x-devil.fr:993/imap/ssl/novalidate-cert}'.$box; // Todo : remove 'novalidate-cert'

            try {
                $mailbox = new Mailbox($hostname, $admin_email, $this->decryptPassword($admin_email_password));
                $mailsIds = $mailbox->searchMailbox();
                $mails = $mailbox->getMailsInfo($mailsIds);
            } catch (Exception $e) {

            }
        }

        return $this->render('admin/mail/mailbox.html.twig', [
            'mails' => $mails,
            'box' => $box
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
