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
use Symfony\Component\Validator\Constraints\NotBlank;

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
            ])
            ->add('round', EntityType::class, [
                'class' => GameRound::class,
                'placeholder' => '- Choisir -',
                'constraints' => [new NotBlank()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
        ]);
    }
}
