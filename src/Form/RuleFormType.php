<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Rule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * The measure of a computed rule is fixed: only its coefficient is a field.
 *
 * @extends AbstractType<Rule>
 */
class RuleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rule = $builder->getData();

        if (!$rule instanceof Rule || !$rule->isComputed()) {
            $builder->add('measure', options: ['label' => 'Mesure', 'required' => true]);
        }

        $builder->add('coefficient', IntegerType::class, [
            'label' => 'Coefficient',
            'constraints' => [new NotNull()],
            // An empty coefficient stays a form error instead of reaching the int property.
            'setter' => static function (Rule $rule, ?int $coefficient): void {
                if ($coefficient !== null) {
                    $rule->setCoefficient($coefficient);
                }
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rule::class,
        ]);
    }
}
