<?php

namespace App\Form;

use App\Entity\AuthUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.full_name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.name_placeholder'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email_address',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'form.email_placeholder'
                ]
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'form.password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'form.password_placeholder'
                    ],
                    'constraints' => [
                        new NotBlank([
                            'message' => 'form.password_required',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'form.password_min_length',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'form.confirm_password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'form.confirm_password_placeholder'
                    ],
                ],
                'invalid_message' => 'form.password_mismatch',
                'mapped' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AuthUser::class,
            'translation_domain' => 'messages',
        ]);
    }
}
