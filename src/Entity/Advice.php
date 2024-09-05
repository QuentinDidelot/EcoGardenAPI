<?php

namespace App\Entity;

use App\Repository\AdviceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdviceRepository::class)]
class Advice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $adviceText = null;

    #[ORM\Column]
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
