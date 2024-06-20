<?php
namespace App\Entity;

use App\Repository\MenuItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MenuItemRepository::class)]
class MenuItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['menu_item', 'comment'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['menu_item', 'comment'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['menu_item', 'comment'])]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Groups(['menu_item', 'comment'])]
    private ?string $price = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['menu_item', 'comment'])]
    private ?string $image_url = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: "menuItem", cascade: ["remove"])]
    #[Groups(['menu_item'])]
    private Collection $comments;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'menuItems')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['menu_item'])]
    private ?Category $category = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    // ... getters and setters ...
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;
        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setMenuItem($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getMenuItem() === $this) {
                $comment->setMenuItem(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }
}
