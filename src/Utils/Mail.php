<?php

namespace App\Utils;

class Mail
{
    private string $from;
    private string $fromName;
    private array $to = [];
    private array $cc = [];
    private array $cci = [];
    private string $subject = "";
    private string $textHtml = "";
    private string $textPlain = "";
    private array $attachments = [];

    public function __construct(string $from, string $fromName) {
        $this->from = $from;
        $this->fromName = $fromName;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * @return array
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @param array $to
     */
    public function setTo(array $to): void
    {
        $this->to = $to;
    }

    /**
     * @return array
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * @param array $cc
     */
    public function setCc(array $cc): void
    {
        $this->cc = $cc;
    }

    /**
     * @return array
     */
    public function getCci(): array
    {
        return $this->cci;
    }

    /**
     * @param array $cci
     */
    public function setCci(array $cci): void
    {
        $this->cci = $cci;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getTextHtml(): string
    {
        return $this->textHtml;
    }

    /**
     * @param string $textHtml
     */
    public function setTextHtml(string $textHtml): void
    {
        $this->textHtml = $textHtml;
    }

    /**
     * @return string
     */
    public function getTextPlain(): string
    {
        return $this->textPlain;
    }

    /**
     * @param string $textPlain
     */
    public function setTextPlain(string $textPlain): void
    {
        $this->textPlain = $textPlain;
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param array $attachments
     */
    public function setAttachments(array $attachments): void
    {
        $this->attachments = $attachments;
    }
}