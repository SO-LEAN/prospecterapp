<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateOrganizationForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('corporateName')
            ->add('form')
            ->add('language', Type\CountryType::class, ['preferred_choices' => ['GB', 'FR', 'DE'], 'required' => false])
            ->add('email', Type\EmailType::class)
            ->add('phoneNumber', Type\TelType::class)
            ->add('street')
            ->add('postalCode')
            ->add('city')
            ->add('country', Type\CountryType::class, ['preferred_choices' => ['BE', 'EN', 'FR', 'GB'], 'required' => false])
            ->add('city')
            ->add('observations', Type\TextareaType::class)

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
