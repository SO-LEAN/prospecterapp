<?php

namespace App;

use Imagine\Image\ImagineInterface;

/**
 * Makes the copy of an image in a size, once: the copy is written next to the originals, nginx serves it
 * afterwards. Never enlarges: a smaller image stays as it is, blurring it would only make it heavier.
 */
final readonly class Resizer
{
    public function __construct(private Files $files, private ImagineInterface $imagine)
    {
    }

    /**
     * @return string the path of the copy
     */
    public function copy(string $originalPath, ImageSize $size): string
    {
        $copy = $this->files->absolute($size->pathOf($originalPath));

        if (!is_file($copy)) {
            $this->make($this->files->original($originalPath), $copy, $size);
        }

        return $copy;
    }

    private function make(string $original, string $copy, ImageSize $size): void
    {
        $image = $this->imagine->open($original);

        if ($size->value < $image->getSize()->getWidth()) {
            $image->resize($image->getSize()->widen($size->value));
        }

        if (!is_dir(dirname($copy))) {
            mkdir(dirname($copy), 0o775, true);
        }

        $image->save($copy);
    }
}
