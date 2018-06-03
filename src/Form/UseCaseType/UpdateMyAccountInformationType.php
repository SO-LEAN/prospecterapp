<?php

namespace App\Form\UseCaseType;

use App\Form\EasyImportFileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UpdateMyAccountInformationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userName')
            ->add('password', Type\PasswordType::class)
            ->add('firstName')
            ->add('lastName')
            ->add('email', Type\EmailType::class)
            ->add('phoneNumber', Type\TelType::class)
            ->add('language', Type\CountryType::class, ['preferred_choices' => ['GB', 'FR', 'DE'], 'required' => false])
            ->add('organizationCorporateName')
            ->add('organizationForm')
            ->add('picture', EasyImportFileType::class)
            ->add('organizationLogo', EasyImportFileType::class)
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }
}
