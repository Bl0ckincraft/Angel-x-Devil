<?php

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class MailFormData
{
    public string $subject = "";
    public string $message = "";
    public array $toList = [];
    public array $cc = [];
    public array $cci = [];
    public array $attachments = [];

    public function clearAttachments(): void
    {
        $this->attachments = [];
    }

    public function valuesAsKeys(array $array): array
    {
        $arr = [];

        foreach ($array as $val) {
            $arr[$val] = null;
        }

        return $arr;
    }

    public function send(string $username, string $decryptedPassword, string $fromAddress, string $fromName, EntityManagerInterface $entityManager, KernelInterface $kernel, SluggerInterface $slugger): Mail
    {
        $id = time().'-'.rand();
        $mail = new Mail($fromAddress, $fromName);

        $mail->setSubject($this->subject);
        $mail->setTextHtml($this->message);
        $mail->setTextPlain($this->message);
        $mail->setTo(MailUtils::getAndUpdateUsers($this->valuesAsKeys($this->toList), $entityManager));
        $mail->setCc(MailUtils::getAndUpdateUsers($this->valuesAsKeys($this->cc), $entityManager));
        $mail->setCci(MailUtils::getAndUpdateUsers($this->valuesAsKeys($this->cci), $entityManager));

        $folder = $kernel->getProjectDir() . DIRECTORY_SEPARATOR . "attachment". DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($folder)) {
            mkdir($folder, recursive: true);
        }

        $newAttachments = [];

        /** @var UploadedFile $attachment */
        foreach ($this->attachments as $attachment) {
            $originalFilename = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'.'.$attachment->getClientOriginalExtension();

            $attachment->move($folder, $newFilename);
            $newAttachments[$folder.DIRECTORY_SEPARATOR.$newFilename] = $originalFilename.'.'.$attachment->getClientOriginalExtension();
        }

        $mail->setAttachments($newAttachments);

        try {
            MailUtils::sendMail($mail, $username, $decryptedPassword);
        } catch (Exception $e) {

        }

        $this->deleteFolder($folder);

        return $mail;
    }

    public static function deleteFolder(string $folder): void
    {
        if (!is_dir($folder)) {
            unlink($folder);
        }

        $content = scandir($folder);

        foreach ($content as $file) {
            if ($file != "." && $file != "..") {
                $path = $folder . '/' . $file;
                if (is_dir($path)) {
                    self::deleteFolder($path);
                } else {
                    unlink($path);
                }
            }
        }

        rmdir($folder);
    }
}