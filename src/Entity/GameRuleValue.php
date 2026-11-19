<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Value of a free rule in a game, entered by the referee once the game is
 * stopped. The values of the computed rules are never stored.
 */
#[ORM\Entity]
#[ORM\UniqueConstraint(fields: ['game', 'rule'])]
class GameRuleValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $value = 0;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'ruleValues')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Game $game,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Rule $rule,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): static
    {
        $this->value = $value;

        return $this;
    }
}
