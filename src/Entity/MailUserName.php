<?php

namespace App\Entity;

use App\Repository\MailUserNameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MailUserNameRepository::class)]
class MailUserName
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastKnownName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getLastKnownName(): ?string
    {
        return $this->lastKnownName;
    }

    public function setLastKnownName(?string $lastKnownName): self
    {
        $this->lastKnownName = $lastKnownName;

        return $this;
    }
}
