<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use function Symfony\Component\Translation\t;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn]
    private ?User $user = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $carrierName = null;

    #[ORM\Column]
    private ?float $carrierPrice = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryFirstname = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryLastname = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryPostal = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryCity = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryCountry = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryPhone = null;

    #[ORM\Column]
    private ?bool $isPaid = null;

    #[ORM\OneToMany(mappedBy: 'associatedOrder', targetEntity: OrderData::class)]
    private Collection $orderData;

    #[ORM\Column(length: 255)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    public function __construct()
    {
        $this->orderData = new ArrayCollection();
    }

    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->getOrderData() as $orderData) {
            $total += $orderData->getTotalPrice();
        }

        $total += $this->getCarrierPrice();

        return $total;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCarrierName(): ?string
    {
        return $this->carrierName;
    }

    public function setCarrierName(string $carrierName): self
    {
        $this->carrierName = $carrierName;

        return $this;
    }

    public function getCarrierPrice(): ?float
    {
        return $this->carrierPrice;
    }

    public function setCarrierPrice(float $carrierPrice): self
    {
        $this->carrierPrice = $carrierPrice;

        return $this;
    }

    public function getDeliveryFirstname(): ?string
    {
        return $this->deliveryFirstname;
    }

    public function setDeliveryFirstname(string $deliveryFirstname): self
    {
        $this->deliveryFirstname = $deliveryFirstname;

        return $this;
    }

    public function getDeliveryLastname(): ?string
    {
        return $this->deliveryLastname;
    }

    public function setDeliveryLastname(string $deliveryLastname): self
    {
        $this->deliveryLastname = $deliveryLastname;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getDeliveryPostal(): ?string
    {
        return $this->deliveryPostal;
    }

    public function setDeliveryPostal(string $deliveryPostal): self
    {
        $this->deliveryPostal = $deliveryPostal;

        return $this;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(string $deliveryCity): self
    {
        $this->deliveryCity = $deliveryCity;

        return $this;
    }

    public function getDeliveryCountry(): ?string
    {
        return $this->deliveryCountry;
    }

    public function setDeliveryCountry(string $deliveryCountry): self
    {
        $this->deliveryCountry = $deliveryCountry;

        return $this;
    }

    public function getDeliveryPhone(): ?string
    {
        return $this->deliveryPhone;
    }

    public function setDeliveryPhone(string $deliveryPhone): self
    {
        $this->deliveryPhone = $deliveryPhone;

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): self
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    /**
     * @return Collection<int, OrderData>
     */
    public function getOrderData(): Collection
    {
        return $this->orderData;
    }

    public function addOrderData(OrderData $orderData): self
    {
        if (!$this->orderData->contains($orderData)) {
            $this->orderData->add($orderData);
            $orderData->setAssociatedOrder($this);
        }

        return $this;
    }

    public function removeOrderData(OrderData $orderData): self
    {
        if ($this->orderData->removeElement($orderData)) {
            // set the owning side to null (unless already changed)
            if ($orderData->getAssociatedOrder() === $this) {
                $orderData->setAssociatedOrder(null);
            }
        }

        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }
}
