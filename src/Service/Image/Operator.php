<?php

namespace App\Service\Image;

use Imagine\Image\ImagineInterface;
use Symfony\Component\HttpFoundation\File\File;

interface Operator
{
    /**
     * @param ImagineInterface $imagine
     */
    public function setDriver(ImagineInterface $imagine): void;

    /**
     * @param File  $origin
     * @param array $args
     *
     * @return File
     */
    public function execute(File $origin, array $args): File;
}
