<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\BingoDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<BingoDto>
 */
class BingoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('round', IntegerType::class, ['attr' => ['min' => 1, 'pattern' => '[0-9]*']])
            ->add('season', IntegerType::class, ['label' => 'Saison', 'attr' => ['min' => 1, 'pattern' => '[0-9]*']])
            ->add('squares', options: [
                'compound' => true,
                'allow_extra_fields' => true,
                'getter' => fn (BingoDto $bingoDto): array => $bingoDto->squares,
                'setter' => function (BingoDto $bingoDto, mixed $value, FormInterface $form): void {
                    $bingoDto->squares = $form->getExtraData();
                },
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
