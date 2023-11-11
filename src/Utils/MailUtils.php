<?php

namespace App\Utils;

use App\Entity\MailUserName;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpImap\Mailbox;
use Symfony\Component\HttpKernel\KernelInterface;

class MailUtils
{
    public static array $boxData = [
        'inbox' => [
            'displayName' => 'Boîte de réception',
            'imapName' => 'INBOX'
        ],
        'sent' => [
            'displayName' => 'Envoyés',
            'imapName' => 'Sent'
        ],
        'trash' => [
            'displayName' => 'Corbeille',
            'imapName' => 'Trash'
        ],
        'spams' => [
            'displayName' => 'Spams',
            'imapName' => 'Junk'
        ],
        'drafts' => [
            'displayName' => 'Brouillons',
            'imapName' => 'Drafts'
        ]
    ];

    public static function readBox(string $email, string $decrypted_password, string $imapBoxName): array
    {
        $mails = [];

        if ($email != null && $decrypted_password != null) {
            $hostname = '{angel-x-devil.fr:993/imap/ssl}'.$imapBoxName; // Todo : remove 'novalidate-cert'

            try {
                $mailbox = new Mailbox($hostname, $email, $decrypted_password);
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
                        'fromMail' => $fromMail == $email ? $mailInfo->to : $fromMail,
                        'fromName' => $fromMail == $email ? $mailInfo->to : $fromName,
                        'date' => gmdate('d/m/Y', $mailInfo->udate),
                        'size' => $size > 1024 ? $size > 1024 * 1024 ? round($size / (1024 * 1024)).'mo' : round($size / 1024).'ko' : $size.'o'
                    ];
                }
            } catch (Exception $e) {

            }
        }

        return $mails;
    }

    public static function readMail(string $email, string $decrypted_password, string $imapBoxName, int $id, EntityManagerInterface $entityManager, KernelInterface $kernel): array | null
    {
        $mail = null;

        if ($email != null && $decrypted_password != null) {
            $hostname = '{angel-x-devil.fr:993/imap/ssl}' . $imapBoxName; // Todo : remove 'novalidate-cert'

            $mailbox = new Mailbox($hostname, $email, $decrypted_password);
            $mailData = $mailbox->getMail($id, true);
            $mailInfo = $mailbox->getMailsInfo([$id])[0];

            $mail = [
                'id' => $mailInfo->uid,
                'seen' => $mailInfo->seen == 1,
                'subject' => $mailInfo->subject,
                'fromName' => $mailData->fromName,
                'fromMail' => $mailData->fromAddress,
                'date' => gmdate('d/m/Y', $mailInfo->udate),
                'draft' => $mailInfo->draft == 1,
                'deleted' => $mailInfo->deleted == 1,
                'flagged' => $mailInfo->flagged == 1,
                'answered' => $mailInfo->answered == 1,
                'size' => MailUtils::formatSize($mailInfo->size),
                'attachments' => [],
                'textHtml' => $mailData->textHtml,
                'textPlain' => $mailData->textPlain,
                'to' => self::getAndUpdateUsers($mailData->to, $entityManager),
                'cc' => self::getAndUpdateUsers($mailData->cc, $entityManager),
                'replyTo' => self::getAndUpdateUsers($mailData->replyTo, $entityManager)
            ];

            $inbox = imap_open($hostname, $email, $decrypted_password);
            $ms = imap_search($inbox, 'ALL');
            rsort($ms);
            $structure = imap_fetchstructure($inbox, $ms[0]);

            $attachments = array();

            /* if any attachments found... */
            if (isset($structure->parts) && count($structure->parts)) {
                for ($i = 0; $i < count($structure->parts); $i++) {
                    $attachments[$i] = array(
                        'is_attachment' => false,
                        'filename' => '',
                        'name' => '',
                        'attachment' => ''
                    );

                    if ($structure->parts[$i]->ifdparameters) {
                        foreach ($structure->parts[$i]->dparameters as $object) {
                            if (strtolower($object->attribute) == 'filename') {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['filename'] = $object->value;
                            }
                        }
                    }

                    if ($structure->parts[$i]->ifparameters) {
                        foreach ($structure->parts[$i]->parameters as $object) {
                            if (strtolower($object->attribute) == 'name') {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['name'] = $object->value;
                            }
                        }
                    }

                    if ($attachments[$i]['is_attachment']) {
                        $attachments[$i]['attachment'] = imap_fetchbody($inbox, $ms[0], $i + 1);

                        /* 3 = BASE64 encoding */
                        if ($structure->parts[$i]->encoding == 3) {
                            $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                        } /* 4 = QUOTED-PRINTABLE encoding */
                        elseif ($structure->parts[$i]->encoding == 4) {
                            $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                        }
                    }
                }
            }

            /* iterate through each attachment and save it */
            foreach ($attachments as $attachment) {
                if ($attachment['is_attachment'] == 1) {
                    $filename = $attachment['name'];
                    if (empty($filename)) $filename = $attachment['filename'];

                    if (empty($filename)) $filename = time() . ".dat";
                    $folder = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . "attachment" . DIRECTORY_SEPARATOR . MailUtils::getMailName($email);
                    if (!is_dir($folder)) {
                        mkdir($folder);
                    }

                    $finalFileName = $ms[0] . "-" . $filename;
                    $finalPath = $folder . DIRECTORY_SEPARATOR . $finalFileName;
                    $fp = fopen($finalPath, "w+");
                    fwrite($fp, $attachment['attachment']);
                    fclose($fp);
                    $mail['attachments'][$attachment['name']] = [
                        'file_name' => $filename,
                        'file_ms_id' => $ms[0],
                        'file_size' => MailUtils::formatSize(filesize($finalPath))
                    ];
                }
            }
        }

        return $mail;
    }

    public static function encryptPasswordWithKey(string $password, string $key): string
    {
        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted_password = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv.$encrypted_password);
    }

    public static function decryptPasswordWithKey(string $encrypted_password, string $key): string
    {
        $encrypted_password = base64_decode($encrypted_password);

        $iv_length = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($encrypted_password, 0, $iv_length);
        $encrypted_password = substr($encrypted_password, $iv_length);

        return openssl_decrypt($encrypted_password, 'AES-256-CBC', $key, 0, $iv);
    }

    public static function getMailName(string $mail): string
    {
        return explode("@", $mail)[0];
    }

    public static function formatSize(int $size): string
    {
        $unitInt = floor(log($size, 1024));
        return round($size / pow(1024, $unitInt), 2). self::sizeUnitFromInt($unitInt, 'o');
    }

    public static function sizeUnitFromInt(int $unitInt, string $typeName): string
    {
        switch ($unitInt) {
            case 0:
                return $typeName;
            case 1:
                return 'K'.$typeName;
            case 2:
                return 'M'.$typeName;
            case 3:
                return 'G'.$typeName;
            case 4:
                return 'T'.$typeName;
            default:
                return '';
        }
    }

    public static function getAndUpdateUsers(array $users, EntityManagerInterface $entityManager): array
    {
        $repository = $entityManager->getRepository(MailUserName::class);
        $result = [];

        foreach ($users as $address => $name) {
            /** @var MailUserName | null $savedData */
            $savedData = $repository->findOneBy([
                'address' => $address
            ]);

            if (!$savedData) {
                $savedData = new MailUserName();

                $savedData->setAddress($address);
                $savedData->setLastKnownName($name);
                $entityManager->persist($savedData);
            } else if (!$savedData->getLastKnownName() || ($name && strcmp($savedData->getLastKnownName(), $name) != 0)) {
                $savedData->setLastKnownName($name);
            }

            /** @var User | null $user */
            $user = $entityManager->getRepository(User::class)->findOneBy([
                'admin_email' => $address
            ]);

            if ($user) {
                $savedData->setLastKnownName($user->getFullName());
            }

            $result[$savedData->getAddress()] = $savedData->getLastKnownName();
        }

        $entityManager->flush();
        return $result;
    }
}