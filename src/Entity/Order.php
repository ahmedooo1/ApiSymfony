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
}
