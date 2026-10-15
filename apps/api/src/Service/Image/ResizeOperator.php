<?php

namespace App\Service\Image;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Symfony\Component\HttpFoundation\File\File;

class ResizeOperator implements Operator
{
    private ?ImagineInterface $imagine = null;

    public function setDriver(ImagineInterface $imagine): void
    {
        $this->imagine = $imagine;
    }

    public function execute(File $origin, array $args): File
    {
        [$width, $height] = $args;
        $target = sprintf('%s/%s', sys_get_temp_dir(), md5(uniqid()));
        if (0 === $width && 0 === $height) {
            throw new \InvalidArgumentException();
        }

        if (0 === $width || 0 === $height) {
            $this->ratioResizing($origin, $args, $target);
        } else {
            $this->standardResizing($origin, $args, $target);
        }

        return new File($target);
    }

    /**
     * Keeps the ratio and never enlarges: a smaller image stays as it is, blurring it would only make it heavier.
     *
     * @param array<int, mixed> $args
     */
    private function ratioResizing(File $origin, array $args, string $target): void
    {
        $image = $this->imagine->open($origin->getRealPath());
        $size = $image->getSize();

        if (0 === $args[0] && $args[1] < $size->getHeight()) {
            $image->resize($size->heighten($args[1]));
        } elseif (0 === $args[1] && $args[0] < $size->getWidth()) {
            $image->resize($size->widen($args[0]));
        }

        $image->save($target);
    }

    /**
     * @param array<int, mixed> $args
     */
    private function standardResizing(File $origin, array $args, string $target): void
    {
        $box = new Box($args[0], $args[1]);
        $this->imagine->open($origin->getRealPath())
            ->resize($box)
            ->save($target);
    }
}
