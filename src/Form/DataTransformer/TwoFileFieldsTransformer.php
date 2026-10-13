<?php

namespace App\Form\DataTransformer;

use App\Service\System\FileSystemAdapter;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TwoFileFieldsTransformer implements DataTransformerInterface
{
    public function __construct(private readonly FileSystemAdapter $fileSystemAdapter, private readonly string $manual, private readonly string $url)
    {
    }

    public function transform(mixed $value): mixed
    {
        return $value;
    }

    public function reverseTransform(mixed $value): mixed
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

        if (!$this->fileSystemAdapter->copy($value[$this->url], $file, $opts)) {
            throw new TransformationFailedException(sprintf('Cannot download file : "%s"', $value[$this->url]));
        }

        return new UploadedFile($file, $originalName);
    }
}
