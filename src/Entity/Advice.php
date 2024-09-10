<?php

namespace App\Entity;

use App\Repository\AdviceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdviceRepository::class)]
class Advice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getAdvice"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getAdvice"])]
    #[Assert\NotBlank(message: "Le conseil doit être obligatoire")]
    private ?string $adviceText = null;

    #[ORM\Column]
    #[Groups(["getAdvice"])]
    #[Assert\NotBlank(message: 'Le mois est obligatoire (de 1 à 12)')]
    #[Assert\Range(min: 1, max: 12)]
    private ?int $month = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAdviceText(): ?string
    {
        return $this->adviceText;
    }

    public function setAdviceText(string $adviceText): static
    {
        $this->adviceText = $adviceText;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }
}
