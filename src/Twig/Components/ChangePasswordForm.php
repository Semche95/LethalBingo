<?php

namespace App\Twig\Components;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Validator\NotContainingUsername;
use App\Validator\NotCurrentPassword;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class ChangePasswordForm extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @return FormInterface<array<string, mixed>|null>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ChangePasswordFormType::class);
    }

    /**
     * Rules of the new password, each marked as met or not by the value being
     * typed, re-rendered as the user types.
     *
     * @return list<array{label: string, met: bool}>
     */
    public function getNewPasswordCriteria(): array
    {
        $password = $this->formValues['plainPassword']['first'] ?? '';
        $password = \is_string($password) ? $password : '';

        return array_map(fn (Constraint $constraint): array => [
            'label' => match (true) {
                $constraint instanceof Length => $constraint->minMessage,
                $constraint instanceof PasswordStrength, $constraint instanceof NotContainingUsername, $constraint instanceof NotCurrentPassword => $constraint->message,
                default => throw new LogicException(\sprintf('No label for the "%s" password rule.', $constraint::class)),
            },
            'met' => $password !== '' && \count($this->validator->validate($password, $constraint)) === 0,
        ], ChangePasswordFormType::newPasswordCriteria());
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
