<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['cat','all_recette'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: 'Votre titre est trop court',
        maxMessage: 'Votre titre est trop long',
    )]
    #[Assert\NotBlank]
    #[Groups(['cat','all_recette'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['cat','all_recette'])]
    private ?string $content = null;

    #[ORM\Column]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'd-m-Y à H:i:s'])]
    #[Groups(['cat'])]
    private ?\DateTimeImmutable $created_at = null;

    // #[Groups(['cat'])]
    // private ?string $_links = null;

    #[ORM\OneToMany(targetEntity: Recette::class, mappedBy: 'category')]
    private Collection $recettes;


    public function __construct()
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->recettes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }


    // public function getLinks(): ?array
    // {
    //     return array(
    //         'self' => '/api/category/' .$this->getId(),
    //         'update' => '/api/category/' .$this->getId(),
    //         'delete' => '/api/category/' .$this->getId(),
    //     );
    // }


    /**
     * @return Collection<int, Recette>
     */
    public function getRecettes(): Collection
    {
        return $this->recettes;
    }

    public function addRecette(Recette $recette): static
    {
        if (!$this->recettes->contains($recette)) {
            $this->recettes->add($recette);
            $recette->setCategory($this);
        }

        return $this;
    }

    public function removeRecette(Recette $recette): static
    {
        if ($this->recettes->removeElement($recette)) {
            // set the owning side to null (unless already changed)
            if ($recette->getCategory() === $this) {
                $recette->setCategory(null);
            }
        }

        return $this;
    }
}
