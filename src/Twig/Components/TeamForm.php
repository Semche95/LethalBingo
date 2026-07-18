<?php

namespace App\Twig\Components;

use App\Entity\Team;
use App\Form\TeamFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
final class TeamForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?Team $team;

    #[LiveProp]
    public string $mode;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            TeamFormType::class,
            $this->team
        );
    }

    #[LiveAction]
    #[IsGranted('ROLE_REFEREE')]
    public function save(EntityManagerInterface $em): RedirectResponse
    {
        $this->submitForm();

        /** @var Team $team */
        $team = $this->getForm()->getData();
        $em->persist($team);
        $em->flush();

        $this->addFlash('success', 'Équipe enregistrée');

        return $this->redirectToRoute('app_referee_team_edit', [
            'id' => $team->getId(),
        ]);
    }

    #[LiveAction]
    public function addCollectionItem(PropertyAccessorInterface $propertyAccessor, #[LiveArg] string $name): void
    {
        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);

        if (!\is_array($data)) {
            $propertyAccessor->setValue($this->formValues, $propertyPath, []);
            $data = [];
        }

        $index = [] !== $data ? max(array_keys($data)) + 1 : 0;
        $propertyAccessor->setValue($this->formValues, $propertyPath.'['.$index.']', ['name' => '', 'captain' => false]);

        $this->ensureCaptainSelected();
    }

    #[LiveAction]
    public function removeCollectionItem(
        PropertyAccessorInterface $propertyAccessor,
        #[LiveArg] string $name,
        #[LiveArg] int $index,
    ): void {
        $propertyPath = $this->fieldNameToPropertyPath($name, $this->formName);
        $data = $propertyAccessor->getValue($this->formValues, $propertyPath);
        unset($data[$index]);
        $propertyAccessor->setValue($this->formValues, $propertyPath, $data);

        $this->ensureCaptainSelected();
    }

    private function ensureCaptainSelected(): void
    {
        $members = $this->formValues['members'] ?? [];

        if (empty($members)) {
            return;
        }

        foreach ($members as $member) {
            if (!empty($member['captain'])) {
                return;
            }
        }

        $firstKey = array_key_first($members);
        $this->formValues['members'][$firstKey]['name'] ??= '';
        $this->formValues['members'][$firstKey]['captain'] = '1';
    }
}
