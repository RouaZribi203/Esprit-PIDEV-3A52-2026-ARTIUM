<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\Role;
use App\Enum\Statut;
use App\Enum\Specialite;
use App\Enum\CentreInteret;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('photoProfil', FileType::class, [
            'label' => 'Photo de profil',
            'mapped' => false,
            'required' => false,
            'attr' => [
                'class' => 'form-control',
            ],
        ]);
        $isEdit = $options['is_edit'];
        
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new \Symfony\Component\Validator\Constraints\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/u',
                        'message' => 'Le nom ne doit contenir que des lettres, espaces, apostrophes et tirets'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new \Symfony\Component\Validator\Constraints\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/u',
                        'message' => 'Le prénom ne doit contenir que des lettres, espaces, apostrophes et tirets'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prénom'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'L\'email est obligatoire']),
                    new Email(['message' => 'L\'email n\'est pas valide']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'L\'email ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@email.com'
                ]
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La date de naissance ne peut pas être vide']),
                    new \Symfony\Component\Validator\Constraints\LessThan([
                        'value' => 'today',
                        'message' => 'La date de naissance doit être dans le passé'
                    ]),
                    new \Symfony\Component\Validator\Constraints\GreaterThan([
                        'value' => '-120 years',
                        'message' => 'La date de naissance n\'est pas valide'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('numTel', TelType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Le numéro de téléphone ne peut pas être vide']),
                    new \Symfony\Component\Validator\Constraints\Regex([
                        'pattern' => '/^[2459]\d{7}$/',
                        'message' => 'Le numéro de téléphone doit contenir 8 chiffres et commencer par 2, 4, 5 ou 9'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+216 XX XXX XXX'
                ]
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La ville ne peut pas être vide']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'La ville doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez la ville'
                ]
            ])
            ->add('role', EnumType::class, [
                'class' => Role::class,
                'label' => 'Rôle',
                'required' => true,
                'choice_label' => fn(Role $role) => $role->value,
                'placeholder' => 'Choisir un rôle',
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'user_role'
                ]
            ])
            ->add('statut', EnumType::class, [
                'class' => Statut::class,
                'label' => 'Statut',
                'required' => true,
                'choice_label' => fn(Statut $statut) => $statut->value,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('biographie', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Parlez-nous de vous...'
                ]
            ])
            ->add('specialite', EnumType::class, [
                'class' => Specialite::class,
                'label' => 'Spécialité',
                'choice_label' => fn(Specialite $specialite) => $specialite->value,
                'placeholder' => 'Choisissez une spécialité',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'user_specialite_field'
                ]
            ])
            ->add('centreInteret', EnumType::class, [
                'class' => CentreInteret::class,
                'label' => 'Centre d\'intérêt',
                'choice_label' => fn(CentreInteret $centre) => $centre->value,
                'required' => false,
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'user_centreInteret_field'
                ]
            ]);
        
        // Ajouter le champ mot de passe uniquement lors de la création
        if (!$isEdit) {
            $builder->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le mot de passe'
                ]
            ]);
        }


        // Événement POST_SUBMIT : Nettoyer les champs conditionnels après soumission
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $user = $event->getData();
            
            if ($user) {
                // Si c'est un AMATEUR, mettre specialite à null
                if ($user->getRole() === Role::AMATEUR) {
                    $user->setSpecialite(null);
                }
                // Si c'est un ARTISTE, mettre centreInteret à null
                elseif ($user->getRole() === Role::ARTISTE) {
                    $user->setCentreInteret(null);
                }
                // For ADMIN, nettoyer les deux
                elseif ($user->getRole() === Role::ADMIN) {
                    $user->setSpecialite(null);
                    $user->setCentreInteret(null);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'validation_groups' => function ($form) {
                $isEdit = false;
                if (is_object($form) && method_exists($form, 'getConfig')) {
                    $isEdit = $form->getConfig()->getOption('is_edit', false);
                }
                return $isEdit ? ['Default'] : ['Default', 'create'];
            },
        ]);
    }
}