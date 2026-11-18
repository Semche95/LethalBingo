<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Rule;
use App\Repository\RuleRepository;
use App\Service\RefreshEvent;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveListener;

#[AsLiveComponent]
#[IsGranted('ROLE_REFEREE')]
final readonly class RuleList
{
    public function __construct(
        private RuleRepository $ruleRepository,
    ) {
    }

    /**
     * @return list<Rule>
     */
    public function getRules(): array
    {
        return $this->ruleRepository->findAllOrdered();
    }

    // The rules are read at each render: refreshing is only re-rendering.
    #[LiveListener(RefreshEvent::RuleList->value)]
    public function __invoke(): void
    {
    }
}
