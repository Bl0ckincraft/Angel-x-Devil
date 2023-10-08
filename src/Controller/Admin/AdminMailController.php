<?php

namespace App\Controller\Admin;

use App\Controller\Base\AbstractAppController;
use App\Entity\User;
use Exception;
use PhpImap\Mailbox;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use function Webmozart\Assert\Tests\StaticAnalysis\length;

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
                $mailsInfo = $mailbox->getMailsInfo($mailsIds);

                usort($mailsInfo, function($a, $b) { return $b->udate - $a->udate; });

                foreach ($mailsInfo as $mailInfo) {
                    $size = $mailInfo->size;
                    $from = $mailInfo->from;

                    $fromMail = str_replace(['<', '>'], '', explode(' ', $from)[sizeof(explode(' ', $from)) - 1]);
                    $fromName = substr_replace($from, '', -strlen(' <'.$fromMail.'>'));

                    $mails[] = [
                        'id' => $mailInfo->uid,
                        'seen' => $mailInfo->seen == 1,
                        'subject' => $mailInfo->subject,
                        'fromMail' => $fromMail == $admin_email ? $mailInfo->to : $fromMail,
                        'fromName' => $fromMail == $admin_email ? $mailInfo->to : $fromName,
                        'date' => gmdate('d/m/Y', $mailInfo->udate),
                        'size' => $size > 1024 ? $size > 1024 * 1024 ? round($size / (1024 * 1024)).'mo' : round($size / 1024).'ko' : $size.'o'
                    ];
                }
            } catch (Exception $e) {

            }
        }

        $boxNames = [
            'INBOX' => 'Boîte de réception',
            'Sent' => 'Envoyés',
            'Trash' => 'Corbeille',
            'Junk' => 'Spams',
            'Drafts' => 'Brouillons'
        ];

        return $this->render('admin/mail/mailbox.html.twig', [
            'mails' => $mails,
            'box' => $box,
            'boxName' => $boxNames[$box]
        ]);
    }

    #[Route('/admin/mailbox/{box}/read/{id}', name: 'app_admin_mailbox_read')]
    public function read(string $box, int $id, KernelInterface $kernel): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $admin_email = $user->getAdminEmail();
        $admin_email_password = $user->getAdminEmailPassword();

        $mail = null;

        if ($admin_email != null && $admin_email_password != null) {
            $hostname = '{angel-x-devil.fr:993/imap/ssl/novalidate-cert}'.$box; // Todo : remove 'novalidate-cert'

            try {
                $mailbox = new Mailbox($hostname, $admin_email, $this->decryptPassword($admin_email_password));
                $mailData = $mailbox->getMail($id, true);
                $mailInfo = $mailbox->getMailsInfo([$id])[0];

                $mail = [
                    'id' => $mailInfo->uid,
                    'seen' => $mailInfo->seen == 1,
                    'subject' => $mailInfo->subject,
                    'fromMail' => $mailData->fromName,
                    'fromName' => $mailData->fromAddress,
                    'date' => gmdate('d/m/Y', $mailInfo->udate),
                ];

                $inbox = imap_open($hostname, $admin_email, $this->decryptPassword($admin_email_password));
                $ms = imap_search($inbox, 'ALL');
                rsort($ms);
                $structure = imap_fetchstructure($inbox, $ms[0]);

                $attachments = array();

                /* if any attachments found... */
                if(isset($structure->parts) && count($structure->parts))
                {
                    for($i = 0; $i < count($structure->parts); $i++)
                    {
                        $attachments[$i] = array(
                            'is_attachment' => false,
                            'filename' => '',
                            'name' => '',
                            'attachment' => ''
                        );

                        if($structure->parts[$i]->ifdparameters)
                        {
                            foreach($structure->parts[$i]->dparameters as $object)
                            {
                                if(strtolower($object->attribute) == 'filename')
                                {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['filename'] = $object->value;
                                }
                            }
                        }

                        if($structure->parts[$i]->ifparameters)
                        {
                            foreach($structure->parts[$i]->parameters as $object)
                            {
                                if(strtolower($object->attribute) == 'name')
                                {
                                    $attachments[$i]['is_attachment'] = true;
                                    $attachments[$i]['name'] = $object->value;
                                }
                            }
                        }

                        if($attachments[$i]['is_attachment'])
                        {
                            $attachments[$i]['attachment'] = imap_fetchbody($inbox, $ms[0], $i+1);

                            /* 3 = BASE64 encoding */
                            if($structure->parts[$i]->encoding == 3)
                            {
                                $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                            }
                            /* 4 = QUOTED-PRINTABLE encoding */
                            elseif($structure->parts[$i]->encoding == 4)
                            {
                                $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                            }
                        }
                    }
                }

                /* iterate through each attachment and save it */
                foreach($attachments as $attachment)
                {
                    if($attachment['is_attachment'] == 1)
                    {
                        $filename = $attachment['name'];
                        if(empty($filename)) $filename = $attachment['filename'];

                        if(empty($filename)) $filename = time() . ".dat";
                        $folder = $kernel->getProjectDir()."\\attachment";
                        if(!is_dir($folder))
                        {
                            mkdir($folder);
                        }
                        $fp = fopen($folder ."\\". $ms[0] . "-" . $filename, "w+");
                        fwrite($fp, $attachment['attachment']);
                        fclose($fp);
                    }
                }
                
                dd($mailData, $mailInfo, $mail, $ms[0], __DIR__, __NAMESPACE__, $kernel->getProjectDir());
            } catch (Exception $e) {
                dd($e);
            }
        }

        return $this->render('admin/mail/mail.html.twig', [
            'mail' => $mail
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
