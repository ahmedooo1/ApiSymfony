<?php

namespace App\Entity;

use App\Repository\RecetteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RecetteRepository::class)]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['all_recette'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 3,
        max: 200,
        minMessage: "Le titre doit faire au moins 3 caractères",
        maxMessage: "Le titre ne peut pas faire plus de 200 caractères"
    )]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Groups(['all_recette'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(
        min: 3,
        max: 2000,
        minMessage: "La description doit faire au moins 3 caractères",
        maxMessage: "La description ne peut pas faire plus de 2000 caractères"
    )]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Groups(['all_recette'])]
    private ?string $description = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(['facile', 'moyen', 'difficile'])]
    #[Assert\NotBlank(message: "Veuillez sélectionner une difficulté")]
    #[Groups(['all_recette'])]
    private ?string $difficulty = null;

    #[ORM\Column]
    #[Groups(['all_recette'])]
    #[Context([DateTimeNormalizer::FORMAT_KEY => 'd-m-Y à H:i:s'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Veuillez sélectionner une difficulté")]
    #[Assert\Positive(message: 'Cette valeur doit être positive.')]
    #[Assert\LessThan(value: 300)]
    #[Groups(['all_recette'])]
    private ?int $timeprepare = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'Cette valeur doit être positive.')]
    #[Groups(['all_recette'])]
    private ?int $timecook = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 4000,
        maxMessage: "Le conseil ne peut pas faire plus de 4000 caractères"
    )]
    #[Groups(['all_recette'])]
    private ?string $conseil = null;

    #[ORM\ManyToOne(inversedBy: 'recettes')]
    #[Groups(['all_recette'])]
    private ?Category $category = null;


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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): static
    {
        $this->difficulty = $difficulty;

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

    public function getTimeprepare(): ?int
    {
        return $this->timeprepare;
    }

    public function setTimeprepare(?int $timeprepare): static
    {
        $this->timeprepare = $timeprepare;

        return $this;
    }

    public function getTimecook(): ?int
    {
        return $this->timecook;
    }

    public function setTimecook(?int $timecook): static
    {
        $this->timecook = $timecook;

        return $this;
    }

    public function getConseil(): ?string
    {
        return $this->conseil;
    }

    public function setConseil(?string $conseil): static
    {
        $this->conseil = $conseil;

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
