<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RuleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Scoring rule: a measure and its coefficient, the points per unit of the
 * measure. The application computes the value of a computed rule; the referee
 * enters the value of a free rule when the game is stopped.
 */
#[ORM\Entity(repositoryClass: RuleRepository::class)]
class Rule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Null for a free rule.
    #[ORM\Column(length: 30, unique: true, nullable: true, enumType: ComputedMeasure::class)]
    private ?ComputedMeasure $computedMeasure = null;

    // Null for a computed rule.
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\When(expression: 'this.isComputed() === false', constraints: [new Assert\NotBlank()])]
    #[Assert\Length(max: 255)]
    private ?string $measure = null;

    #[ORM\Column]
    private int $coefficient = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComputedMeasure(): ?ComputedMeasure
    {
        return $this->computedMeasure;
    }

    public function setComputedMeasure(?ComputedMeasure $computedMeasure): static
    {
        $this->computedMeasure = $computedMeasure;

        return $this;
    }

    public function isComputed(): bool
    {
        return $this->computedMeasure !== null;
    }

    public function getMeasure(): ?string
    {
        return $this->measure;
    }

    public function setMeasure(?string $measure): static
    {
        $this->measure = $measure;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->computedMeasure?->label() ?? (string) $this->measure;
    }

    public function getCoefficient(): int
    {
        return $this->coefficient;
    }

    public function setCoefficient(int $coefficient): static
    {
        $this->coefficient = $coefficient;

        return $this;
    }
}
