<?php

namespace App;

use Imagine\Image\ImagineInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Makes the copy of an image in a size, once: the copy is put in the bucket next to the original, and served
 * from there afterwards. Never enlarges: a smaller image stays as it is, blurring it would only make it heavier.
 */
final readonly class Resizer
{
    public function __construct(private Bucket $bucket, private ImagineInterface $imagine)
    {
    }

    public function copy(string $originalKey, ImageSize $size): StoredFile
    {
        return $this->bucket->fileAt($size->pathOf($originalKey)) ?? $this->make($originalKey, $size);
    }

    private function make(string $originalKey, ImageSize $size): StoredFile
    {
        $original = $this->bucket->fileAt($originalKey) ?? throw new NotFoundHttpException(sprintf('No file %s', $originalKey));
        $image = $this->imagine->load($original->contents);

        if ($size->value < $image->getSize()->getWidth()) {
            $image->resize($image->getSize()->widen($size->value));
        }

        $copy = new StoredFile($image->get(pathinfo($originalKey, PATHINFO_EXTENSION)), $original->contentType);
        $this->bucket->put($size->pathOf($originalKey), $copy);

        return $copy;
    }
}
