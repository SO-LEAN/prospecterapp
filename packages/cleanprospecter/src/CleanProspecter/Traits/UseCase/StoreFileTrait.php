<?php

declare(strict_types=1);

namespace Solean\CleanProspecter\Traits\UseCase;

use Solean\CleanProspecter\Entity\File;
use SplFileInfo;

/**
 * Requires a Solean\CleanProspecter\Gateway\Storage in $this->storage.
 */
trait StoreFileTrait
{
    /**
     * The file metadata is read before Storage::add(), which may move the file away.
     */
    private function storeFile(SplFileInfo $file): File
    {
        $extension = $file->getExtension();
        $size = $file->getSize();

        return File::fromValues($this->storage->add($file), $extension, $size);
    }
}
