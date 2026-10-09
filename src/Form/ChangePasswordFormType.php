<?php

namespace App\Form;

use App\Validator\NotContainingUsername;
use App\Validator\NotCurrentPassword;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class ChangePasswordFormType extends AbstractType
{
    /**
     * Rules of a new password, also listed under the field and checked as the user
     * types: each message is the label of its line in that list.
     *
     * @return list<Constraint>
     */
    public static function newPasswordCriteria(): array
    {
        return [
            new Length(min: 12, max: 4096, minMessage: 'Au moins 12 caractères', maxMessage: 'Au plus 4096 caractères'),
            new PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM, message: 'Assez robuste : mélangez lettres, chiffres et symboles, ou une phrase de plusieurs mots'),
            new NotContainingUsername(),
            new NotCurrentPassword(),
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'translation_domain' => false,
                'always_empty' => false,
                'attr' => [
                    'autocomplete' => 'current-password',
                ],
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer votre mot de passe actuel'),
                    new UserPassword(message: 'Le mot de passe actuel est incorrect.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'translation_domain' => false,
                'options' => [
                    'always_empty' => false,
                ],
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'translation_domain' => false,
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'translation_domain' => false,
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre.',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un mot de passe'),
                    ...self::newPasswordCriteria(),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
