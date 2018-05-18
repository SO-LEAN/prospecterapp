<?php

namespace App\Form\DataTransformer;

use \Exception;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TwoFileFieldsTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $manual;

    /**
     * @var string
     */
    private $url;

    /**
     * @param string $manual
     * @param string $url
     */
    public function __construct(string $manual, string $url)
    {
        $this->manual = $manual;
        $this->url = $url;
    }

    /**
     * @inheritDoc
     */
    public function transform($value)
    {
       return [
           $this->url =>  $value,
       ];
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($value)
    {
        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        if (null !== $value[$this->manual]) {
            return $value[$this->manual];

        }

        if (null === $value[$this->url]) {
            return null;
        }

        $opts = stream_context_create(['http' => ['method'=>"GET"]]);
        $file = tempnam(sys_get_temp_dir(), 'down');
        $originalName = array_reverse(explode('/', $value[$this->url]))[0];

        try {
            $isDownloaded = copy($value[$this->url], $file, $opts);
        } catch (Exception $e) {
            $isDownloaded = false;
        }

        if(!$isDownloaded) {
            throw new TransformationFailedException(sprintf('Cannot download file : "%s"', $value[$this->url]));
        }

        return  new UploadedFile($file, $originalName);
    }
}
