<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Rule;
use App\Form\RuleFormType;
use App\Service\RefreshEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;

#[AsLiveComponent]
final class RuleForm extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Rule $rule = null;

    public function __construct(
        private readonly LiveResponder $liveResponder,
    ) {
    }

    /**
     * @return FormInterface<Rule|null>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(RuleFormType::class, $this->rule);
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function save(EntityManagerInterface $em): void
    {
        $this->submitForm();

        $em->persist($this->getForm()->getData());
        $em->flush();

        $this->liveResponder->emit(RefreshEvent::RuleList->value);
        $this->liveResponder->dispatchBrowserEvent('rule:saved');
    }
}
