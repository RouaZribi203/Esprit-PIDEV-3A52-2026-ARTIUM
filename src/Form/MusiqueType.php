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
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter music title',
                    'minlength' => 3,
                    'maxlength' => 255,
                    'required' => 'required'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Title is required']),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Title must be at least 3 characters long',
                        'max' => 255,
                        'maxMessage' => 'Title cannot exceed 255 characters'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter music description',
                    'rows' => 3,
                    'minlength' => 10,
                    'maxlength' => 5000,
                    'required' => 'required'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Description is required']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Description must be at least 10 characters long',
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
                'placeholder' => 'Select a genre',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Genre is required'])
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Cover Image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/jpg'
                ],
                'help' => 'Max 5MB (JPEG, PNG) • Recommended: min 300x300px',
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5242880', // 5MB in bytes
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG or PNG)',
                        'maxSizeMessage' => 'Image file is too large (max 5MB)',
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
                'label' => 'Audio File',
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
