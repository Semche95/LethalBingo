<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Game;
use App\Entity\GameRound;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class GameFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'label' => 'Équipe',
                'placeholder' => '- Choisir -',
                'constraints' => [new NotBlank()],
                'attr' => [
                    'data-controller' => 'symfony--ux-autocomplete--autocomplete single-select-blur',
                ],
            ])
            ->add('round', EntityType::class, [
                'class' => GameRound::class,
                'placeholder' => '- Choisir -',
                'choice_label' => function (GameRound $gameRound): string {
                    $label = (string) $gameRound;

                    if (!$gameRound->isComplete()) {
                        $label .= ' (incomplet)';
                    }

                    return $label;
                },
                'choice_attr' => function (GameRound $gameRound): array {
                    if (!$gameRound->isComplete()) {
                        return ['disabled' => 'disabled'];
                    }

                    return [];
                },
                'constraints' => [
                    new NotBlank(message: 'Veuillez sélectionner un bingo.'),
                    new Callback(static function (?GameRound $gameRound, ExecutionContextInterface $context): void {
                        if ($gameRound !== null && !$gameRound->isComplete()) {
                            $context->buildViolation('Le bingo sélectionné est incomplet et ne peut pas être utilisé pour une partie.')
                                ->addViolation();
                        }
                    }),
                ],
                'attr' => [
                    'data-controller' => 'symfony--ux-autocomplete--autocomplete single-select-blur',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
