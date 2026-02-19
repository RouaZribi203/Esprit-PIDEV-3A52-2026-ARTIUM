<?php

namespace App\Form;

use App\Entity\Collections;
use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\TextType;    
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CollectionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
        'label' => 'Titre',
        'attr' => ['placeholder' => 'Entrez le titre', 'class' => 'form-control'],
        'constraints' => [
            new Assert\NotBlank(['message' => 'Le titre ne peut pas être vide.']),
        ],
    ])
    ->add('description', TextareaType::class, [
        'label' => 'Description',
        'attr' => ['placeholder' => 'Entrez la description (10 caractères minimum)', 'rows' => 4, 'class' => 'form-control'],
        'constraints' => [
            new Assert\NotBlank(['message' => 'La description ne peut pas être vide.']),
            new Assert\Length([
                'min' => 10,
                'max' => 5000,
                'minMessage' => 'La description doit comporter au moins {{ limit }} caractères.',
                'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
            ]),
        ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collections::class,
        ]);
    }
}
