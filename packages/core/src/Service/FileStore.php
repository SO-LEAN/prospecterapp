<?php

declare(strict_types=1);

namespace Prospecter\Core\Service;

use Prospecter\Core\Entity\File;
use Prospecter\Core\Gateway\Storage;

final readonly class FileStore
{
    public function __construct(private Storage $storage)
    {
    }

    public function store(\SplFileInfo $upload): File
    {
        $extension = $upload->getExtension();
        $size = $upload->getSize();

        return new File($this->storage->add($upload), $extension, (int) $size);
    }
}
