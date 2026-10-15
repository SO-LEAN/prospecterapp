<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Service;

use Prospecter\Core\Application\Port\Storage;
use Prospecter\Core\Domain\Shared\File;

final readonly class FileStore
{
    public function __construct(private Storage $storage)
    {
    }

    public function store(\SplFileInfo $upload): File
    {
        $extension = $upload->getExtension();
        $size = (int) $upload->getSize();

        return new File($this->storage->add($upload), $extension, $size);
    }

    public function discard(File $file): void
    {
        $this->storage->remove($file->url);
    }
}
