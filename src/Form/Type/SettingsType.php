<?php

/**
 * This file is part of the contentful/the-example-app package.
 *
 * @copyright 2015-2018 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace App\Form\Type;

use App\Validator\Constraints\ContentfulCredentials;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('spaceId', TextType::class, [
                'constraints' => new NotBlank(['message' => 'fieldIsRequiredLabel']),
                'required' => true,
            ])
            ->add('deliveryToken', TextType::class, [
                'constraints' => new NotBlank(['message' => 'fieldIsRequiredLabel']),
                'required' => true,
            ])
            ->add('previewToken', TextType::class, [
                'constraints' => new NotBlank(['message' => 'fieldIsRequiredLabel']),
                'required' => true,
            ])
            ->add('editorialFeatures', CheckboxType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [new ContentfulCredentials()],
        ]);
    }
}
