<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`order`')]  
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $clientName;

    #[ORM\Column(type: 'string', length: 255)]
    private $clientEmail;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $address;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $paymentMethod;

    #[ORM\Column(type: 'string', length: 255)]
    private $city;

    #[ORM\Column(type: 'string', length: 255)]
    private $postalCode;

    #[ORM\Column(type: 'boolean')]
    private $isPaid = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $client;

    #[ORM\Column(type: 'datetime')]
    private $orderedAt;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private $items;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getOrderedAt(): ?\DateTimeInterface
    {
        return $this->orderedAt;
    }

    public function setOrderedAt(\DateTimeInterface $orderedAt): self
    {
        $this->orderedAt = $orderedAt;

        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        $this->items[] = $item;
        $item->setOrder($this);

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        $this->items->removeElement($item);

        return $this;
    }

    public function setClientName(string $clientName): self
    {
        $this->clientName = $clientName;
        return $this;
    }

    public function setClientEmail(string $clientEmail): self
    {
        $this->clientEmail = $clientEmail;
        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setIsPaid(bool $isPaid): self
    {
        $this->isPaid = $isPaid;
        return $this;
    }

    public function getIsPaid(): ?bool
    {
        return $this->isPaid;
    }
}
