<?php

namespace App\Form;

use App\Entity\Collections;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Enum\TypeOeuvre;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OeuvreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l’œuvre',
            ])
            ->add('type', ChoiceType::class, [
            'choices' => TypeOeuvre::cases(),
            'choice_label' => fn(TypeOeuvre $choice) => $choice->value,
            'choice_value' => fn(?TypeOeuvre $choice) => $choice?->value ?? '',
            'placeholder' => 'Sélectionnez un type',
            'required' => true,
             ])
            ->add('description')
            ->add('date_creation')
            ->add('image', FileType::class, [
            'label' => 'Image',
            'mapped' => false,      // IMPORTANT : on ne lie pas directement à l’attribut BLOB
            ])
            ->add('collection', EntityType::class, [
                'class' => Collections::class,
                'choice_label' => 'id',
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Oeuvre::class,
        ]);
    }
}
