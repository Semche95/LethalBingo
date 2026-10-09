<?php

namespace App\Twig\Components;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ChangePasswordForm extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    /**
     * @return FormInterface<array<string, mixed>|null>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ChangePasswordFormType::class);
    }

    #[LiveAction]
    #[IsGranted('IS_AUTHENTICATED')]
    public function save(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): RedirectResponse
    {
        $this->submitForm();

        /** @var User $user */
        $user = $this->getUser();
        /** @var array{plainPassword: string} $data */
        $data = $this->getForm()->getData();

        $user->setPassword($passwordHasher->hashPassword($user, $data['plainPassword']));
        $em->flush();

        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

        return $this->redirectToRoute('app_user_profile');
    }
}
