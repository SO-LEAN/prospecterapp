<?php

namespace App\Form\UseCaseType;

use App\Form\EasyImportFileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateMyAccountInformationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('userName')
            ->add('password', PasswordType::class)
            ->add('firstName')
            ->add('lastName')
            ->add('email', EmailType::class)
            ->add('phoneNumber', TelType::class)
            ->add('language', CountryType::class, ['preferred_choices' => ['GB', 'FR', 'DE'], 'required' => false])
            ->add('organizationCorporateName')
            ->add('organizationForm')
            ->add('picture', EasyImportFileType::class)
            ->add('organizationLogo', EasyImportFileType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }
}
