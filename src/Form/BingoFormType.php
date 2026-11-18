<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\BingoDto;
use App\Entity\Rule;
use App\Repository\RuleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<BingoDto>
 */
class BingoFormType extends AbstractType
{
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('round', IntegerType::class, ['attr' => ['min' => 1, 'pattern' => '[0-9]*']])
            ->add('season', IntegerType::class, ['label' => 'Saison', 'attr' => ['min' => 1, 'pattern' => '[0-9]*']])
            ->add('rules', EntityType::class, [
                'label' => 'Règles',
                'help' => 'Règles de scoring appliquées aux parties de ce round',
                'help_attr' => ['class' => 'mb-2'],
                'class' => Rule::class,
                'choices' => $this->ruleRepository->findAllOrdered(),
                'choice_label' => fn (Rule $rule): string => $rule->getLabel(),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            // Squares indexed by row then column, rendered by the BingoCard component
            ->add('squares', CollectionType::class, [
                'entry_type' => CollectionType::class,
                'entry_options' => [
                    'entry_type' => BingoSquareFormType::class,
                    'allow_add' => true,
                    'prototype' => false,
                ],
                'allow_add' => true,
                'prototype' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BingoDto::class,
            'translation_domain' => false,
        ]);
    }
}
