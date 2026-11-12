<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\GameDto;
use App\Entity\GameRound;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<GameDto>
 */
class GameFormType extends AbstractType
{
    // Searchable select that loses focus once a choice is made.
    private const array SELECT_OPTIONS = [
        'placeholder' => '- Choisir -',
        'attr' => [
            'data-controller' => 'symfony--ux-autocomplete--autocomplete single-select-blur',
        ],
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'label' => 'Équipe',
                ...self::SELECT_OPTIONS,
            ])
            ->add('round', EntityType::class, [
                'class' => GameRound::class,
                'label' => 'Bingo',
                ...self::SELECT_OPTIONS,
                'choice_label' => fn (GameRound $gameRound): string => $gameRound->isComplete() ? (string) $gameRound : $gameRound . ' (incomplet)',
                'choice_attr' => fn (GameRound $gameRound): array => $gameRound->isComplete() ? [] : ['disabled' => 'disabled'],
            ])
            ->add('hasCountdown', CheckboxType::class, [
                'label' => 'Limiter le temps',
                'required' => false,
                'attr' => ['data-referee--game-form-target' => 'hasCountdown'],
            ])
            ->add('countdownHours', IntegerType::class, [
                'label' => 'Heures',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => GameDto::MAX_COUNTDOWN_HOURS,
                    'data-referee--game-form-target' => 'countdownHours',
                ],
            ])
            ->add('countdownMinutes', IntegerType::class, [
                'label' => 'Minutes',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'max' => GameDto::MAX_COUNTDOWN_MINUTES,
                    'data-referee--game-form-target' => 'countdownMinutes',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameDto::class,
        ]);
    }
}
