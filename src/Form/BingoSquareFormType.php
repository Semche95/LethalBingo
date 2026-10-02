<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One square of the bingo grid, as an array (see BingoDto's BingoSquareData).
 * The fields are rendered by the BingoCard component.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class BingoSquareFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('category', HiddenType::class)
            // The label is stored as typed
            ->add('label', TextareaType::class, ['trim' => false])
            ->add('visible', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => false,
        ]);
    }
}
