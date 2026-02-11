<?php

namespace App\Form;

use App\Entity\Musique;
use App\Enum\GenreMusique;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class MusiqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter music title'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a title'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter music description',
                    'rows' => 3
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a description'])
                ]
            ])
            ->add('genre', EnumType::class, [
                'class' => GenreMusique::class,
                'label' => 'Genre',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Select genre',
                'constraints' => [
                    new NotBlank(['message' => 'Please select a genre'])
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Cover Image',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG)',
                    ])
                ]
            ])
            ->add('audioFile', FileType::class, [
                'label' => 'Audio File',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'audio/*'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please upload an audio file']),
                    new File([
                        'maxSize' => '20M',
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
                        'mimeTypesMessage' => 'Please upload a valid audio file (MP3, WAV, AAC, OPUS, OGG, FLAC, M4A)',
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Musique::class,
        ]);
    }
}
