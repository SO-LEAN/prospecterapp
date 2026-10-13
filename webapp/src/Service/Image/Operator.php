<?php

namespace App\Service\Image;

use Imagine\Image\ImagineInterface;
use Symfony\Component\HttpFoundation\File\File;

interface Operator
{
    public function setDriver(ImagineInterface $imagine): void;

    public function execute(File $origin, array $args): File;
}
