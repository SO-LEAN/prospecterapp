<?php

namespace App\Form\UseCaseType;

use App\Form\EasyImportFileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateOrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('corporateName')
            ->add('form')
            ->add('type')
            ->add('language', CountryType::class, ['preferred_choices' => ['GB', 'FR', 'DE'], 'required' => false])
            ->add('email', EmailType::class)
            ->add('phoneNumber', TelType::class)
            ->add('street')
            ->add('postalCode')
            ->add('city')
            ->add('country', CountryType::class, ['preferred_choices' => ['BE', 'EN', 'FR', 'GB'], 'required' => false])
            ->add('city')
            ->add('observations', TextareaType::class)
            ->add('logo', EasyImportFileType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }
}
