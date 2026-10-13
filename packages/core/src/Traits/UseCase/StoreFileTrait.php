<?php

declare(strict_types=1);

namespace Prospecter\Core\Traits\UseCase;

use Prospecter\Core\Entity\File;
use SplFileInfo;

/**
 * Requires a Prospecter\Core\Gateway\Storage in $this->storage.
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
