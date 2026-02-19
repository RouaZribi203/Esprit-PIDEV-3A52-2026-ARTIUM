<?php

namespace App\Form;

use App\Entity\Collections;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Oeuvre;
use App\Entity\User;
use App\Enum\TypeOeuvre;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class OeuvreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de l’œuvre',
            ])
            ->add('type', ChoiceType::class, [
    'choices' => [
        TypeOeuvre::PEINTURE,
        TypeOeuvre::SCULPTURE,
        TypeOeuvre::PHOTOGRAPHIE,
    ],

    'choice_label' => fn(TypeOeuvre $choice) => $choice->value,
    'choice_value' => fn(?TypeOeuvre $choice) => $choice?->value ?? '',
    'placeholder' => 'Sélectionnez un type',
    'required' => true,
])
            ->add('description')
            ->add('image', FileType::class, [
            'label' => 'Image',
            'mapped' => false,      
            'required' => false,
            ])
            ->add('collection', EntityType::class, [
                'class' => Collections::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir une collection',
                'attr' => [
                    'class' => 'form-select',
                ],
                'query_builder' => function ($er) use ($options) {
                    if (isset($options['user']) && $options['user'] instanceof User) {
                        return $er->createQueryBuilder('c')
                            ->where('c.artiste = :user')
                            ->setParameter('user', $options['user']);
                    }
                    return $er->createQueryBuilder('c')->where('1=0'); // aucune collection si pas de user
                },
            ]);
            if ($options['include_date']) {
            $builder->add('date_creation', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
            ]);
            }
            
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Oeuvre::class,
            'include_date' => true,
            'user' => null,
        ]);
    }
}
