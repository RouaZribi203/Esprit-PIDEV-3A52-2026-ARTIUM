<?php

namespace App\Form;

use App\Entity\Reclamation;
use App\Enum\TypeReclamation;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Reclamation1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('texte')
            ->add('type', EnumType::class, [
                'class' => TypeReclamation::class,
                'choice_label' => static fn (TypeReclamation $choice): string => $choice->value,
                'placeholder' => 'Selectionner une categorie',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
