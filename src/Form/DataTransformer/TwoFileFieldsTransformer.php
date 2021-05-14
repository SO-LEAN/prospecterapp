<?php

namespace App\Form\DataTransformer;

use App\Service\System\FileSystemAdapter;
use Exception;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TwoFileFieldsTransformer implements DataTransformerInterface
{
    /**
     * @var FileSystemAdapter
     */
    private $fileSystemAdapter;
    /**
     * @var string
     */
    private $manual;

    /**
     * @var string
     */
    private $url;

    public function __construct(FileSystemAdapter $fileSystemAdapter, string $manual, string $url)
    {
        $this->fileSystemAdapter = $fileSystemAdapter;
        $this->manual = $manual;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
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

        $opts = stream_context_create(['http' => ['method' => 'GET']]);
        $file = $this->fileSystemAdapter->tempnam(sys_get_temp_dir(), 'down');
        $originalName = array_reverse(explode('/', $value[$this->url]))[0];

        try {
            $isDownloaded = $this->fileSystemAdapter->copy($value[$this->url], $file, $opts);
        } catch (Exception $e) {
            $isDownloaded = false;
        }

        if (!$isDownloaded) {
            throw new TransformationFailedException(sprintf('Cannot download file : "%s"', $value[$this->url]));
        }

        return new UploadedFile($file, $originalName);
    }
}
