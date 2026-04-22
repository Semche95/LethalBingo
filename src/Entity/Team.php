<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints as DoctrineAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\UniqueConstraint(fields: ['name'])]
#[DoctrineAssert\UniqueEntity(fields: ['name'])]
class Team implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    /**
     * @var Collection<int, TeamMember>
     */
    #[ORM\OneToMany(targetEntity: TeamMember::class, mappedBy: 'team', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['captain' => 'DESC', 'name' => 'ASC'])]
    #[Assert\Valid]
    #[AppAssert\UniqueTeamCaptain]
    private Collection $members;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'team')]
    private Collection $games;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->games = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, TeamMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(TeamMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setTeam($this);
        }

        return $this;
    }

    public function removeMember(TeamMember $member): static
    {
        if ($this->members->removeElement($member)) {
            // set the owning side to null (unless already changed)
            if ($member->getTeam() === $this) {
                $member->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setTeam($this);
        }

        return $this;
    }

    public function removeGame(Game $game): static
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getTeam() === $this) {
                $game->setTeam(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return ($this->name ?? '') . (count($this->members) > 0 ? ' (' . implode(', ', $this->members->toArray()) . ')' : '');
    }
}
