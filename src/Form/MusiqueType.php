<?php

namespace App\Form;

use App\Entity\Musique;
use App\Enum\GenreMusique;use App\Validator\ImageDimensions;use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MusiqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Detect if we're editing an existing entity
        $isEdit = $options['data']?->getId() !== null;
        
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'entrez le titre de la musique',
                    'minlength' => 3,
                    'maxlength' => 255,
                    'required' => 'required'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est requis']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Le titre doit comporter au moins 3 caractères',
                        'max' => 255,
                        'maxMessage' => 'Le titre ne peut pas dépasser 255 caractères'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'entrez la description de la musique',
                    'rows' => 3,
                    'minlength' => 10,
                    'maxlength' => 5000,
                    'required' => 'required'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est requise']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'La description doit comporter au moins 10 caractères long',
                        'max' => 5000,
                        'maxMessage' => 'Description cannot exceed 5000 characters'
                    ])
                ]
            ])
            ->add('genre', EnumType::class, [
                'class' => GenreMusique::class,
                'label' => 'Genre',
                'attr' => [
                    'class' => 'form-select',
                    'required' => 'required'
                ],
                'placeholder' => 'Sélectionnez un genre',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le genre est requis'])
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image de Couverture',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/jpg'
                ],
                'help' => 'Max 5MB (JPEG, PNG) • Recommender: min 300x300px',
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5242880', // 5MB in bytes
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier image valide (JPEG ou PNG).',
                        'maxSizeMessage' => 'Le fichier image est trop volumineux. (max 5MB)',
                    ]),
                    new ImageDimensions([
                        'minWidth' => 300,
                        'minHeight' => 300,
                        'maxWidth' => 5000,
                        'maxHeight' => 5000,
                    ])
                ]
            ])
            ->add('audioFile', FileType::class, [
                'label' => 'Fichier Audio',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'audio/*',
                    'data-max-size' => '20971520' // 20MB for JS validation
                ],
                'help' => !$isEdit ? 'Required. Max 20MB (MP3, WAV, AAC, etc.)' : 'Optional. Leave blank to keep current. Max 20MB',
                'constraints' => array_filter([
                    !$isEdit ? new Assert\NotBlank(['message' => 'Audio file is required']) : null,
                    new Assert\File([
                        'maxSize' => '20971520', // 20MB in bytes
                        'mimeTypes' => [
                            'audio/mpeg',
                            'audio/mp3',
                            'audio/wav',
                            'audio/x-wav',
                            'audio/aac',
                            'audio/aacp',
                            'audio/opus',
                            'audio/ogg',
                            'audio/webm',
                            'audio/flac',
                            'audio/x-m4a',
                            'audio/mp4',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid audio file format',
                        'maxSizeMessage' => 'Audio file is too large (max 20MB)',
                    ])
                ])
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Musique::class,
            'attr' => [
                'class' => 'needs-validation',
                'novalidate' => true
            ]
        ]);
    }
}
