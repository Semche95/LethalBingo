<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\GameDto;
use App\Entity\GameRound;
use App\Entity\Team;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<GameDto>
 */
class GameFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'label' => 'Équipe',
                'placeholder' => '- Choisir -',
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
                'attr' => [
                    'data-controller' => 'symfony--ux-autocomplete--autocomplete single-select-blur',
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
