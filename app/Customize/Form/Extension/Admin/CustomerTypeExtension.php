<?php

declare(strict_types=1);

namespace Customize\Form\Extension\Admin;

use Eccube\Form\Type\Admin\CustomerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CustomerTypeExtension extends AbstractTypeExtension
{
    public function getExtendedType(): string
    {
        return CustomerType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [CustomerType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nick_name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('image', HiddenType::class, [
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
            ])
            ->add('checkout_card', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'min' => '16',
                        'max' => '16',
                    ]),
                ],
            ])
            ->add('affiliate', IntegerType::class, [
                'required' => false,    
                'attr' => [
                    'min' => 1,
                    'max' => 100
                ],
            ])
            ->add('authorize_doc', FileType::class, [
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'attr'  => [
                    'accept' => '*',
                    'multiple' => 'multiple'
                ]
            ])
            ->add('is_poster', CheckboxType::class, [
                'label' => '',
                'required' => false,
                'value' => '0',
            ])
            ->add('plan_poster', CheckboxType::class, [
                'required' => false,
                'value' => '0',
                'mapped' => false,
            ])
            ->add('customer_image', FileType::class, [
                'multiple' => true,
                'required' => false,
                'mapped' => false,
            ])
            ->add('add_images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
            ->add('delete_images', CollectionType::class, [
                'entry_type' => HiddenType::class,
                'prototype' => true,
                'mapped' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ]);
    }
}