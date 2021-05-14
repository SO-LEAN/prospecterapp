<?php

namespace App\Service\Image;

use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;

class ResizeOperator implements Operator
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * {@inheritdoc}
     */
    public function setDriver(ImagineInterface $imagine): void
    {
        $this->imagine = $imagine;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(File $origin, array $args): File
    {
        list($width, $height) = $args;
        $target = sprintf('%s/%s', sys_get_temp_dir(), md5(uniqid()));
        if (0 === $width && 0 === $height) {
            throw new InvalidArgumentException();
        }

        if (0 === $width || 0 === $height) {
            $this->ratioResizing($origin, $args, $target);
        } else {
            $this->standardResizing($origin, $args, $target);
        }

        return new File($target);
    }

    /**
     * @param $target
     */
    private function ratioResizing(File $origin, array $args, $target): void
    {
        $image = $this->imagine->open($origin->getRealPath());
        $size = $image->getSize();

        if (0 === $args[0]) {
            $image->resize($size->heighten($args[1]));
        } elseif (0 === $args[1]) {
            $image->resize($size->widen($args[0]));
        }

        $image->save($target);
    }

    /**
     * @param $target
     */
    private function standardResizing(File $origin, array $args, $target): void
    {
        $box = new Box($args[0], $args[1]);
        $this->imagine->open($origin->getRealPath())
            ->resize($box)
            ->save($target);
    }
}
