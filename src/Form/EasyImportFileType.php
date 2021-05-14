<?php

namespace App\Form;

use App\Form\DataTransformer\TwoFileFieldsTransformer;
use App\Service\System\FileSystemAdapter;
use App\Service\System\PhpFileSystemAdapter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class EasyImportFileType extends AbstractType
{
    /**
     * @var FileSystemAdapter
     */
    private $fileSystemAdapter;

    /**
     * @param FileSystemAdapter $fileSystemAdapter
     */
    public function __construct(FileSystemAdapter $fileSystemAdapter = null)
    {
        $this->fileSystemAdapter = $fileSystemAdapter ?? new PhpFileSystemAdapter();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['manual_options']['required'] = $options['required'];
        $options['url_options']['required'] = $options['required'];

        if (!isset($options['options']['error_bubbling'])) {
            $options['options']['error_bubbling'] = $options['error_bubbling'];
        }

        $builder
            ->addModelTransformer(new TwoFileFieldsTransformer($this->fileSystemAdapter, $options['upload_name'], $options['url_name']))
            ->add($options['upload_name'], FileType::class, array_merge($options['options'], $options['manual_options']))
            ->add($options['url_name'], UrlType::class, array_merge($options['options'], $options['url_options']))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'options' => [],
            'manual_options' => [
                'constraints' => [new Image(['mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png'], 'maxSize' => '2M'])],
                ],
            'url_options' => [],
            'error_bubbling' => false,
            'upload_name' => 'image',
            'url_name' => 'orUrl',
        ]);

        $resolver->setAllowedTypes('options', 'array');
        $resolver->setAllowedTypes('manual_options', 'array');
        $resolver->setAllowedTypes('url_options', 'array');
        $resolver->setAllowedTypes('upload_name', 'string');
        $resolver->setAllowedTypes('url_name', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'easy_file';
    }
}
